<?php
/**
 * Konfigurationsdatei für SV Freibad Dabringhausen e.V. - Verkaufserlös Import
 * 
 * @author SV Freibad Dabringhausen e.V.
 * @version 1.0
 * @date 2025-06-09
 */

// Datenbankverbindung
define('DB_HOST', 'localhost');
define('DB_NAME', 'svfd_schedule');
define('DB_USER', 'svfd_Schedule');
define('DB_PASS', 'rq*6X4s82');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// Force UTF-8 for all PHP operations
ini_set('default_charset', 'utf-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Dateipfade
define('CSV_INPUT_DIR', '../dataCheckout/');
define('CSV_ARCHIVE_DIR', '../dataCheckout/csv_archive/');
define('CSV_ERROR_DIR', '../dataCheckout/csv_errors/');
define('LOG_DIR', '../dataCheckout/csv_logs/');

// CSV-Einstellungen
define('CSV_DELIMITER', ';');
define('CSV_ENCLOSURE', '"');
define('CSV_ESCAPE', '\\');

// Dry Run Modus (true = Testlauf, false = echter Import)
define('DRY_RUN_MODE', false);

// E-Mail Konfiguration
define('ADMIN_EMAIL', 'michasalz@gmail.com');
define('SENDER_EMAIL', 'info@freibad-dabringhausen.de');
define('SMTP_HOST', 'freibad-dabringhausen.de');
define('SMTP_PORT', 465);
define('SMTP_USER', 'info@freibad-dabringhausen.de');
define('SMTP_PASS', 'Sabilokizu');
define('SMTP_SECURE', 'ssl'); // 'tls' oder 'ssl'

// Logging-Einstellungen
define('LOG_LEVEL', 'ERROR'); // DEBUG, INFO, WARNING, ERROR
define('LOG_MAX_SIZE', 10485760); // 10MB in Bytes
define('LOG_ROTATE_COUNT', 5);

// Import-Einstellungen
define('MAX_IMPORT_ROWS', 10000); // Maximale Anzahl Zeilen pro Import
define('BATCH_SIZE', 500); // Anzahl Zeilen pro Batch-Insert

// Zeitzone
define('TIMEZONE', 'Europe/Berlin');

// Archivierung
define('ARCHIVE_RETENTION_DAYS', 365); // Aufbewahrung der Archivdateien in Tagen

// Fehlerbehandlung
define('CONTINUE_ON_ERROR', true); // Bei Fehlern in einzelnen Zeilen weitermachen
define('MAX_ERRORS_PER_FILE', 50); // Maximale Anzahl Fehler pro Datei

// Validierung
define('VALIDATE_DATES', true);
define('VALIDATE_PRICES', true);
define('ALLOW_NEGATIVE_PRICES', true); // Für Rückgaben/Stornos

// Duplikat-Prüfung
define('CHECK_DUPLICATES', false);
define('DUPLICATE_CHECK_METHODS', ['file_hash', 'bonnr_check']); // Verfügbare Methoden

// Performance
define('MEMORY_LIMIT', '256M');
define('MAX_EXECUTION_TIME', 300); // 5 Minuten

// Debug-Modus
define('DEBUG_MODE', false);
define('VERBOSE_LOGGING', false);

// Sicherheit
define('ALLOWED_FILE_EXTENSIONS', ['csv', 'txt']);
define('MAX_FILE_SIZE', 5242880); // 5MB in Bytes

// Dashboard-spezifische Einstellungen hinzufügen:

// CORS für lokale Entwicklung (optional)
define('DASHBOARD_CORS', true);

// Cache-Einstellungen für bessere Performance
define('DASHBOARD_CACHE_TIME', 300); // 5 Minuten

// Debug-Modus für Dashboard
define('DASHBOARD_DEBUG', false);

// Maximale Anzahl Einträge für Charts/Tabellen
define('MAX_CHART_POINTS', 30);
define('MAX_TABLE_ROWS', 50);
?>