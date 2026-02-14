# SVFD_old

Sanitized import of the hosting backup (`httpdocs`) for freibad-dabringhausen.de.

## Overview
This repository contains a snapshot of the legacy hosting environment used by the SVFD services. It is **not** a production-ready deployment but a source/archive reference to support migration and maintenance.

## Current Environment (Observed)
- Webserver: nginx (Plesk managed)
- PHP: 8.2.x (header shows 8.2.30)
- TLS: Let’s Encrypt (R12)
- Primary DB: MySQL on `localhost:3306`
- Database name: `svfd_schedule`
- DB user: `svfd_Schedule`
- Mail server: `mail.freibad-dabringhausen.de` (TLS CN = freibad-dabringhausen.de)
- MX: `mail.freibad-dabringhausen.de`

## Major Components
- `schedule/` — Personnel planning application
- `jobs/` — Scheduled jobs and API integrations
- `finanzen/` — Finance tools and imports
- `tagesprotokoll/` — Daily protocol and reporting
- `tagesprotokoll-dashboard/` — Dashboard for Tagesprotokoll data
- `abwasser/`, `frischwasser/`, `dashboard/` — Monitoring and dashboards
- `shiftjuggler-export/` — ShiftJuggler API integration
- `lorawan/`, `modbus/`, `power-dashboard/` — Sensor/monitoring utilities
- `tools/`, `userManagement/`, `webcam/` — Utilities and access helpers

## External Dependencies (Observed)
- OpenWeatherMap API (in `jobs/`)
- ShiftJuggler API (in `shiftjuggler-export/`)
- SMTP sending via domain account (credentials redacted)

## Security Notes
- All secrets and credentials were **redacted** before commit.
- If you use any of these applications, rotate all credentials (DB, SMTP, API keys) on the live systems.

## Exclusions
The following paths are intentionally excluded from this repo:
- `finanzen/dataCheckout/csv_archive/`
- `tagesprotokoll/projekte/rechnungen/`
- `jobs/python/modules/`
- All logs, cache, tmp, `.env` files, and `.htpasswd`

## How to Use This Repo
- Use it as a **reference** for migration, audits, or porting to a new host.
- Do **not** treat it as a deployable environment without adding real secrets and configuration.

## Migration Context
This repo is part of the migration planning for:
- Hosting relocation
- Mail migration to Google Workspace
- Hardening and cleanup

## Deploy Automation
- FTP upload/download + URL checks + log validation:
  - `scripts/deploy/svfd_deploy.sh`
- Optional git hook installer for auto-run on push:
  - `scripts/deploy/install_post_push_hook.sh`
- Setup and usage:
  - `documentation/deploy_automation.md`
