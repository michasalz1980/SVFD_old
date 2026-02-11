# SVFD_old

Sanitized import of the hosting backup (`httpdocs`) for freibad-dabringhausen.de.

## Notes
- Secrets (DB/SMTP/API keys) were **redacted** before commit.
- Logs, tmp, cache, and `.htpasswd` are ignored.
- Large finance CSV archives and PDF invoices are excluded from this repo.

## What is excluded
- `finanzen/dataCheckout/csv_archive/`
- `tagesprotokoll/projekte/rechnungen/`

## Next steps
- Rotate all secrets on the live system (DB, SMTP, OpenWeatherMap, ShiftJuggler, OpenAI).
- Add environment-specific configuration outside of the repo.
