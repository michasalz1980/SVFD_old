#!/usr/bin/env bash
set -Eeuo pipefail

REPO_ROOT="$(git rev-parse --show-toplevel 2>/dev/null || true)"
if [[ -z "$REPO_ROOT" ]]; then
  echo "Not inside a git repository." >&2
  exit 1
fi

HOOK_PATH="$REPO_ROOT/.git/hooks/post-push"
SCRIPT_REL_PATH="scripts/deploy/svfd_deploy.sh"

cat >"$HOOK_PATH" <<'EOF'
#!/usr/bin/env bash
set -Eeuo pipefail

REPO_ROOT="$(git rev-parse --show-toplevel)"
cd "$REPO_ROOT"

DEPLOY_ENV_FILE="${SVFD_DEPLOY_ENV_FILE:-$HOME/.config/svfd/deploy.env}"
if [[ -f "$DEPLOY_ENV_FILE" ]]; then
  set -a
  # shellcheck disable=SC1090
  source "$DEPLOY_ENV_FILE"
  set +a
fi

if [[ "${SVFD_DEPLOY_ON_PUSH:-1}" != "1" ]]; then
  exit 0
fi

TARGET_BRANCH="${SVFD_DEPLOY_BRANCH:-main}"
CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
if [[ "$CURRENT_BRANCH" != "$TARGET_BRANCH" ]]; then
  exit 0
fi

DEPLOY_SCRIPT="${SVFD_DEPLOY_SCRIPT:-scripts/deploy/svfd_deploy.sh}"
if [[ ! -x "$DEPLOY_SCRIPT" ]]; then
  echo "Deploy script not executable or missing: $DEPLOY_SCRIPT" >&2
  exit 1
fi

"$DEPLOY_SCRIPT" ${SVFD_DEPLOY_ARGS:-}
EOF

chmod +x "$HOOK_PATH"
echo "Installed post-push hook: $HOOK_PATH"
echo "Hook reads config from: \${SVFD_DEPLOY_ENV_FILE:-~/.config/svfd/deploy.env}"
