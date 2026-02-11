#!/bin/bash
# Server-Umgebung Check für Freibad Dabringhausen
# Verwendung: chmod +x server_check.sh && ./server_check.sh

echo "=== Server-Umgebung Check ==="
echo

# Webserver ermitteln
echo "--- Webserver ---"
if command -v apache2 &> /dev/null; then
    echo "✓ Apache gefunden: $(apache2 -v | head -1)"
    echo "Apache Module:"
    apache2ctl -M | grep -E "(rewrite|auth|ssl)" | head -5
elif command -v nginx &> /dev/null; then
    echo "✓ NGINX gefunden: $(nginx -v 2>&1)"
else
    echo "⚠ Webserver nicht direkt erkennbar"
    ps aux | grep -E "(httpd|apache|nginx)" | grep -v grep | head -3
fi
echo

# PHP prüfen
echo "--- PHP ---"
if command -v php &> /dev/null; then
    echo "✓ PHP Version: $(php -v | head -1)"
    echo "PHP Erweiterungen (wichtige):"
    for ext in openssl session json hash fileinfo curl; do
        if php -m | grep -q "^$ext$"; then
            echo "  ✓ $ext"
        else
            echo "  ✗ $ext"
        fi
    done
else
    echo "✗ PHP nicht gefunden"
fi
echo

# Berechtigungen
echo "--- Dateisystem ---"
echo "Aktuelle Verzeichnis-Berechtigungen: $(stat -c '%a' .)"
echo "Besitzer: $(stat -c '%U:%G' .)"
echo "Schreibbar: $(test -w . && echo '✓ Ja' || echo '✗ Nein')"
echo

# Webroot ermitteln
echo "--- Webroot ---"
if [ -f "/etc/apache2/sites-enabled/000-default.conf" ]; then
    grep DocumentRoot /etc/apache2/sites-enabled/000-default.conf | head -1
elif [ -f "/etc/nginx/sites-enabled/default" ]; then
    grep -E "root\s+" /etc/nginx/sites-enabled/default | head -1
fi
echo "Aktuelles Verzeichnis: $(pwd)"
echo

# .htaccess Test
echo "--- .htaccess Test ---"
if [ -f ".htaccess" ]; then
    echo "✓ .htaccess vorhanden"
    echo "Inhalt:"
    head -5 .htaccess
else
    echo "⚠ Keine .htaccess gefunden"
    echo "Test-Datei erstellen? (y/n)"
    read -r response
    if [[ "$response" =~ ^[Yy]$ ]]; then
        cat > .htaccess << 'EOF'
# Test .htaccess
RewriteEngine On
# Teste Funktionalität
EOF
        echo "✓ Test-.htaccess erstellt"
    fi
fi
echo

echo "=== Check abgeschlossen ==="
echo "Führen Sie zusätzlich server_info.php im Browser aus für detaillierte PHP-Informationen."