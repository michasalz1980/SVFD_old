#!/usr/bin/env bash
set -Eeuo pipefail

print_usage() {
  cat <<'EOF'
Usage: svfd_deploy.sh [options]

Options:
  --env-file <path>      Path to deploy env file (default: ~/.config/svfd/deploy.env)
  --dry-run              Print actions only
  --download             Download remote backup before upload
  --git-commit           Commit local mirror changes in git after sync steps
  --skip-upload          Skip FTP upload step
  --skip-url-check       Skip HTTP URL checks
  --skip-log-check       Skip log-pattern checks
  --help                 Show this help

Required env vars (in deploy env file):
  SVFD_FTP_HOST
  SVFD_FTP_USER
  SVFD_FTP_PASSWORD
  SVFD_FTP_REMOTE_DIR

Optional env vars:
  SVFD_FTP_PROTOCOL=ftp
  SVFD_FTP_PORT=21
  SVFD_FTP_SSL_ALLOW=1
  SVFD_FTP_SSL_VERIFY=1
  SVFD_DEPLOY_LOCAL_DIR=<repo_root>
  SVFD_DEPLOY_DOWNLOAD_TARGET=timestamped_backup   # or: local_dir
  SVFD_DEPLOY_DOWNLOAD_DELETE=0
  SVFD_DEPLOY_DELETE_REMOTE=0
  SVFD_DEPLOY_URLS=<newline or comma separated URLs>
  SVFD_DEPLOY_HTTP_OK_CODES=200,204,301,302
  SVFD_DEPLOY_URL_TIMEOUT=20
  SVFD_DEPLOY_CURL_INSECURE=0
  SVFD_DEPLOY_LOG_FILES=<newline or comma separated files>
  SVFD_DEPLOY_LOG_PATTERNS=PHP Fatal error|upstream prematurely closed connection|KeyError: 'counter'|TypeError: imagesx
  SVFD_DEPLOY_LOG_TAIL_LINES=2000
  SVFD_DEPLOY_BACKUP_DIR=.deploy-backup
  SVFD_DEPLOY_LOG_DIR=.deploy-logs
  SVFD_DEPLOY_GIT_COMMIT=0
  SVFD_DEPLOY_GIT_COMMIT_MESSAGE="mirror sync"
EOF
}

DRY_RUN=0
DO_DOWNLOAD=0
DO_GIT_COMMIT=0
DO_UPLOAD=1
DO_URL_CHECK=1
DO_LOG_CHECK=1
ENV_FILE="${SVFD_DEPLOY_ENV_FILE:-$HOME/.config/svfd/deploy.env}"

while (($#)); do
  case "$1" in
    --env-file)
      ENV_FILE="${2:-}"
      shift 2
      ;;
    --dry-run)
      DRY_RUN=1
      shift
      ;;
    --download)
      DO_DOWNLOAD=1
      shift
      ;;
    --git-commit)
      DO_GIT_COMMIT=1
      shift
      ;;
    --skip-upload)
      DO_UPLOAD=0
      shift
      ;;
    --skip-url-check)
      DO_URL_CHECK=0
      shift
      ;;
    --skip-log-check)
      DO_LOG_CHECK=0
      shift
      ;;
    --help|-h)
      print_usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      print_usage >&2
      exit 2
      ;;
  esac
done

REPO_ROOT="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
mkdir -p "${SVFD_DEPLOY_LOG_DIR:-$REPO_ROOT/.deploy-logs}"
RUN_ID="$(date +%Y%m%d-%H%M%S)"
RUN_LOG="${SVFD_DEPLOY_LOG_DIR:-$REPO_ROOT/.deploy-logs}/deploy-${RUN_ID}.log"
touch "$RUN_LOG"

log() {
  printf '[%s] %s\n' "$(date +'%F %T')" "$*" | tee -a "$RUN_LOG"
}

fail() {
  log "ERROR: $*"
  exit 1
}

run_cmd() {
  if ((DRY_RUN)); then
    log "DRY-RUN: $*"
    return 0
  fi
  log "RUN: $*"
  eval "$@"
}

run_sensitive_cmd() {
  local label="$1"
  local cmd="$2"
  if ((DRY_RUN)); then
    log "DRY-RUN: $label"
    return 0
  fi
  log "RUN: $label"
  eval "$cmd"
}

check_prereqs() {
  local missing=0
  local cmd
  for cmd in lftp curl grep sed stat wc tail rg; do
    if ! command -v "$cmd" >/dev/null 2>&1; then
      echo "Missing command: $cmd" >&2
      missing=1
    fi
  done
  ((missing == 0)) || fail "Missing required commands."
}

check_env_file_permissions() {
  local mode
  mode="$(stat -c '%a' "$ENV_FILE")"
  if ((10#$mode > 600)); then
    fail "Env file permissions too open ($mode). Use chmod 600 \"$ENV_FILE\"."
  fi
}

load_env() {
  [[ -f "$ENV_FILE" ]] || fail "Env file not found: $ENV_FILE"
  check_env_file_permissions
  set -a
  # shellcheck disable=SC1090
  source "$ENV_FILE"
  set +a
}

required_env() {
  local name
  for name in SVFD_FTP_HOST SVFD_FTP_USER SVFD_FTP_PASSWORD SVFD_FTP_REMOTE_DIR; do
    [[ -n "${!name:-}" ]] || fail "Required variable missing: $name"
  done
}

split_lines() {
  tr ',' '\n' <<<"$1" | sed '/^[[:space:]]*$/d;s/^[[:space:]]*//;s/[[:space:]]*$//'
}

lftp_bool() {
  if [[ "${1:-1}" == "1" ]]; then
    echo "true"
  else
    echo "false"
  fi
}

code_allowed() {
  local code="$1"
  local allowed="${SVFD_DEPLOY_HTTP_OK_CODES:-200,204,301,302}"
  split_lines "$allowed" | grep -Fxq "$code"
}

declare -A LOG_START_LINES

capture_log_offsets() {
  local files_raw="${SVFD_DEPLOY_LOG_FILES:-}"
  local file
  if [[ -z "$files_raw" ]]; then
    return 0
  fi
  while IFS= read -r file; do
    [[ -f "$file" ]] || fail "Configured log file does not exist: $file"
    LOG_START_LINES["$file"]="$(wc -l < "$file")"
  done < <(split_lines "$files_raw")
}

ftp_open_prefix() {
  local protocol="${SVFD_FTP_PROTOCOL:-ftp}"
  local port="${SVFD_FTP_PORT:-21}"
  local ssl_allow ssl_verify
  ssl_allow="$(lftp_bool "${SVFD_FTP_SSL_ALLOW:-1}")"
  ssl_verify="$(lftp_bool "${SVFD_FTP_SSL_VERIFY:-1}")"
  printf 'set cmd:fail-exit true; set net:max-retries 1; set ftp:ssl-allow %s; set ssl:verify-certificate %s; open -u "%s","%s" -p "%s" "%s://%s";' \
    "$ssl_allow" "$ssl_verify" "$SVFD_FTP_USER" "$SVFD_FTP_PASSWORD" "$port" "$protocol" "$SVFD_FTP_HOST"
}

run_download() {
  local target_mode="${SVFD_DEPLOY_DOWNLOAD_TARGET:-timestamped_backup}"
  local target_dir
  local delete_arg=""

  if [[ "$target_mode" == "local_dir" ]]; then
    target_dir="${SVFD_DEPLOY_LOCAL_DIR:-$REPO_ROOT}"
  else
    local backup_dir="${SVFD_DEPLOY_BACKUP_DIR:-$REPO_ROOT/.deploy-backup}"
    mkdir -p "$backup_dir"
    target_dir="$backup_dir/$RUN_ID"
  fi
  mkdir -p "$target_dir"

  if [[ "${SVFD_DEPLOY_DOWNLOAD_DELETE:-0}" == "1" ]]; then
    delete_arg="--delete"
  fi

  local cmd
  cmd="$(cat <<EOF
lftp -e '$(ftp_open_prefix) mirror --verbose --parallel=2 $delete_arg "$SVFD_FTP_REMOTE_DIR" "$target_dir"; bye'
EOF
)"
  run_sensitive_cmd "FTP download mirror ($SVFD_FTP_REMOTE_DIR -> $target_dir)" "$cmd"
}

run_upload() {
  local local_dir="${SVFD_DEPLOY_LOCAL_DIR:-$REPO_ROOT}"
  [[ -d "$local_dir" ]] || fail "Local deploy directory does not exist: $local_dir"

  local delete_arg=""
  if [[ "${SVFD_DEPLOY_DELETE_REMOTE:-0}" == "1" ]]; then
    delete_arg="--delete"
  fi

  local cmd
  cmd="$(cat <<EOF
lftp -e '$(ftp_open_prefix) mirror --reverse --verbose --parallel=2 --only-newer $delete_arg --exclude-glob ".git/" --exclude-glob ".deploy-logs/" --exclude-glob ".deploy-backup/" "$local_dir" "$SVFD_FTP_REMOTE_DIR"; bye'
EOF
)"
  run_sensitive_cmd "FTP upload mirror ($local_dir -> $SVFD_FTP_REMOTE_DIR)" "$cmd"
}

run_url_checks() {
  local urls_raw="${SVFD_DEPLOY_URLS:-}"
  [[ -n "$urls_raw" ]] || fail "SVFD_DEPLOY_URLS is empty. Add URLs or use --skip-url-check."

  local timeout_sec="${SVFD_DEPLOY_URL_TIMEOUT:-20}"
  local insecure_arg=()
  if [[ "${SVFD_DEPLOY_CURL_INSECURE:-0}" == "1" ]]; then
    insecure_arg=(-k)
  fi

  local tmp_body
  tmp_body="$(mktemp)"
  local url code
  local failed=0
  while IFS= read -r url; do
    [[ -n "$url" ]] || continue
    code="$(curl "${insecure_arg[@]}" -sS -o "$tmp_body" -w '%{http_code}' --max-time "$timeout_sec" "$url" || true)"
    if code_allowed "$code"; then
      log "URL OK ($code): $url"
    else
      log "URL FAIL ($code): $url"
      failed=1
    fi
  done < <(split_lines "$urls_raw")
  rm -f "$tmp_body"
  ((failed == 0)) || fail "One or more URL checks failed."
}

run_log_checks() {
  local files_raw="${SVFD_DEPLOY_LOG_FILES:-}"
  [[ -n "$files_raw" ]] || fail "SVFD_DEPLOY_LOG_FILES is empty. Add log files or use --skip-log-check."

  local patterns="${SVFD_DEPLOY_LOG_PATTERNS:-PHP Fatal error|upstream prematurely closed connection|KeyError: 'counter'|TypeError: imagesx}"
  local tail_lines="${SVFD_DEPLOY_LOG_TAIL_LINES:-2000}"

  local file total from tmp slice failed=0
  while IFS= read -r file; do
    [[ -f "$file" ]] || fail "Configured log file does not exist: $file"
    total="$(wc -l < "$file")"
    from="$(( ${LOG_START_LINES["$file"]:-0} + 1 ))"
    if (( total < from )); then
      from=1
    fi
    tmp="$(mktemp)"
    if (( total >= from )); then
      sed -n "${from},\$p" "$file" | tail -n "$tail_lines" > "$tmp"
    else
      : > "$tmp"
    fi
    if rg -n -e "$patterns" "$tmp" >/dev/null 2>&1; then
      log "LOG FAIL: patterns found in $file"
      rg -n -e "$patterns" "$tmp" | head -n 20 | tee -a "$RUN_LOG"
      failed=1
    else
      log "LOG OK: no critical patterns in new lines of $file"
    fi
    rm -f "$tmp"
  done < <(split_lines "$files_raw")
  ((failed == 0)) || fail "Critical patterns found in log checks."
}

run_git_commit() {
  local local_dir="${SVFD_DEPLOY_LOCAL_DIR:-$REPO_ROOT}"
  [[ -d "$local_dir" ]] || fail "Local deploy directory does not exist: $local_dir"

  if ! git -C "$local_dir" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    fail "Local deploy directory is not inside a git work tree: $local_dir"
  fi

  if ((DRY_RUN)); then
    log "DRY-RUN: git -C \"$local_dir\" add -A && git -C \"$local_dir\" commit -m \"...\""
    return 0
  fi

  git -C "$local_dir" add -A
  if git -C "$local_dir" diff --cached --quiet; then
    log "GIT: no changes to commit in $local_dir"
    return 0
  fi

  local msg_prefix="${SVFD_DEPLOY_GIT_COMMIT_MESSAGE:-mirror sync}"
  local msg="$msg_prefix [$RUN_ID]"
  git -C "$local_dir" commit -m "$msg"
  log "GIT: committed changes in $local_dir with message: $msg"
}

main() {
  check_prereqs
  load_env
  required_env
  capture_log_offsets

  log "Deploy started. run_id=$RUN_ID repo_root=$REPO_ROOT log=$RUN_LOG"
  log "Settings: dry_run=$DRY_RUN download=$DO_DOWNLOAD upload=$DO_UPLOAD url_check=$DO_URL_CHECK log_check=$DO_LOG_CHECK"

  if ((DO_DOWNLOAD)); then
    run_download
  fi
  if ((DO_UPLOAD)); then
    run_upload
  fi
  if ((DO_URL_CHECK)); then
    run_url_checks
  fi
  if ((DO_LOG_CHECK)); then
    run_log_checks
  fi
  if ((DO_GIT_COMMIT)) || [[ "${SVFD_DEPLOY_GIT_COMMIT:-0}" == "1" ]]; then
    run_git_commit
  fi

  log "Deploy finished successfully."
}

main "$@"
