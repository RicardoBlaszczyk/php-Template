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
    # Durchsuche die Liste der Pfade
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
    if [ "$1" != "nopause" ]; then
        read -p "Drücken Sie eine beliebige Taste, um fortzufahren..."
    fi
    exit 1
fi

# PHP-Skript ausführen
"$exe" -f updater.php

# Optionale Pause, wenn "nopause" nicht als Argument übergeben wurde
if [ "$1" != "nopause" ]; then
    read -p "Drücken Sie eine beliebige Taste, um fortzufahren..."
fi
