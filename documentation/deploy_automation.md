# Deploy Automation (Issue #7)

This repository now includes deploy automation scripts for FTP upload/download, URL smoke checks, and log pattern checks.

## Files
- `scripts/deploy/svfd_deploy.sh`
- `scripts/deploy/install_post_push_hook.sh`

## 1) Create secure secret file

```bash
mkdir -p ~/.config/svfd
chmod 700 ~/.config/svfd
cat > ~/.config/svfd/deploy.env <<'EOF'
SVFD_FTP_HOST=example.host
SVFD_FTP_USER=example_user
SVFD_FTP_PASSWORD=example_password
SVFD_FTP_PROTOCOL=ftp
SVFD_FTP_PORT=21
SVFD_FTP_SSL_ALLOW=1
SVFD_FTP_SSL_VERIFY=1
SVFD_FTP_REMOTE_DIR=/httpdocs
SVFD_DEPLOY_LOCAL_DIR=/home/michael/Projekte/Entwicklung/SVFD_old
SVFD_DEPLOY_DOWNLOAD_TARGET=timestamped_backup
SVFD_DEPLOY_DOWNLOAD_DELETE=0

# URL checks (comma or newline separated)
SVFD_DEPLOY_URLS=https://personal.freibad-dabringhausen.de/tools/serviceMonitoring.php,https://personal.freibad-dabringhausen.de/webcam/ftp.php,https://personal.freibad-dabringhausen.de/jobs/getWeather.php
SVFD_DEPLOY_HTTP_OK_CODES=200,204,301,302
SVFD_DEPLOY_URL_TIMEOUT=20
SVFD_DEPLOY_CURL_INSECURE=0

# Log checks (comma or newline separated)
SVFD_DEPLOY_LOG_FILES=/home/michael/Projekte/Entwicklung/svfd/_hosting-backup/logs/error_log,/home/michael/Projekte/Entwicklung/svfd/_hosting-backup/logs/proxy_error_log
SVFD_DEPLOY_LOG_PATTERNS="PHP Fatal error|upstream prematurely closed connection|KeyError: 'counter'|TypeError: imagesx"
SVFD_DEPLOY_LOG_TAIL_LINES=2000

# Optional
SVFD_DEPLOY_BACKUP_DIR=/home/michael/Projekte/Entwicklung/SVFD_old/.deploy-backup
SVFD_DEPLOY_LOG_DIR=/home/michael/Projekte/Entwicklung/SVFD_old/.deploy-logs
SVFD_DEPLOY_DELETE_REMOTE=0
SVFD_DEPLOY_GIT_COMMIT=0
SVFD_DEPLOY_GIT_COMMIT_MESSAGE="mirror sync"

# Hook behavior
SVFD_DEPLOY_ON_PUSH=1
SVFD_DEPLOY_BRANCH=main
SVFD_DEPLOY_ARGS=
EOF
chmod 600 ~/.config/svfd/deploy.env
```

Important:
- Never commit credentials to git.
- `svfd_deploy.sh` will fail if the env file is too open (mode > 600).
- If FTPS certificate validation fails, either use the correct FTP host/certificate or set `SVFD_FTP_SSL_VERIFY=0` temporarily.

## 2) Run deploy manually

```bash
chmod +x scripts/deploy/svfd_deploy.sh scripts/deploy/install_post_push_hook.sh
scripts/deploy/svfd_deploy.sh --download
```

Useful variants:
- Dry run: `scripts/deploy/svfd_deploy.sh --dry-run`
- Skip URL checks: `scripts/deploy/svfd_deploy.sh --skip-url-check`
- Skip log checks: `scripts/deploy/svfd_deploy.sh --skip-log-check`
- Custom env file: `scripts/deploy/svfd_deploy.sh --env-file /path/to/deploy.env`
- Commit local mirror changes: `scripts/deploy/svfd_deploy.sh --git-commit`

## Same local directory for download + upload + commit

Use these settings in `deploy.env`:

```bash
SVFD_DEPLOY_LOCAL_DIR=/home/michael/Projekte/Entwicklung/svfd/_hosting-backup
SVFD_DEPLOY_DOWNLOAD_TARGET=local_dir
SVFD_DEPLOY_DOWNLOAD_DELETE=1
SVFD_DEPLOY_GIT_COMMIT=1
SVFD_DEPLOY_GIT_COMMIT_MESSAGE="hosting mirror sync"
```

Then your standard command is:

```bash
scripts/deploy/svfd_deploy.sh --download --git-commit
```

## 3) Enable automatic deploy after push

Install hook:

```bash
scripts/deploy/install_post_push_hook.sh
```

Optional:
- `SVFD_DEPLOY_SCRIPT` to override script location
- `SVFD_DEPLOY_ARGS` to pass extra args (for example `--download`)

## Notes
- Automatic deployment runs only when:
  - `SVFD_DEPLOY_ON_PUSH=1` (from `deploy.env` or environment)
  - current branch matches `SVFD_DEPLOY_BRANCH` (default `main`)
- URL checks and log checks return non-zero on failure.
