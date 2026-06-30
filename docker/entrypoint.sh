#!/bin/sh
set -eu

APP_ROOT="/var/www/html"
CONFIG_DIR="${PHPUPLOADER_CONFIG_DIR:-${APP_ROOT}/config}"
CONFIG_PATH="${CONFIG_DIR}/config.php"
TEMPLATE_PATH="/usr/local/share/phpUploader/config.php.example"

mkdir -p "${CONFIG_DIR}" "${APP_ROOT}/data" "${APP_ROOT}/db" "${APP_ROOT}/logs"

if [ ! -f "${CONFIG_PATH}" ]; then
    cp "${TEMPLATE_PATH}" "${CONFIG_PATH}"
    echo "Initialized ${CONFIG_PATH} from config.php.example. Update the secrets before production use." >&2
fi

chown -R www-data:www-data "${CONFIG_DIR}" "${APP_ROOT}/data" "${APP_ROOT}/db" "${APP_ROOT}/logs"

exec docker-php-entrypoint "$@"
