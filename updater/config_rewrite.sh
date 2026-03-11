#!/bin/bash

# Array mit möglichen PHP-Pfaden
phpLocations=(
    "/usr/bin/php7.0"
    "/usr/bin/php7.1"
    "/usr/bin/php7.2"
    "/usr/bin/php7.3"
    "/usr/bin/php7.4"
    "/usr/bin/php8.0"
    "/usr/bin/php8.1"
    "/usr/bin/php8.2"
    "/usr/bin/php8.3"
    "/usr/bin/php8.4"
    "/usr/bin/php8.5"
    "/usr/bin/php8.6"
    "/opt/plesk/php/8.0/bin/php-cgi"
)

exe=""

# Überprüfen, ob PHP im PATH verfügbar ist
if command -v php >/dev/null 2>&1; then
    exe="php"
else
    # Durchsuche die Liste der möglichen PHP-Pfade
    for location in "${phpLocations[@]}"; do
        if [ -x "$location" ]; then
            exe="$location"
            break
        fi
    done
fi

# Prüfen, ob eine PHP-Installation gefunden wurde
if [ -z "$exe" ]; then
    echo "Konnte keine PHP-Version finden."
    read -p "Drücken Sie eine beliebige Taste, um fortzufahren..." -n1
    exit 1
fi

# PHP-Skript ausführen
"$exe" -f config-rewrite.php

# Pause am Ende
read -p "Drücken Sie eine beliebige Taste, um fortzufahren..." -n1
