#!/bin/bash

# 開発用コード品質チェックスクリプト

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}🔍 コード品質チェックを開始...${NC}"

# Dockerコンテナが起動しているかチェック
if ! docker-compose ps | grep -q "php_cli"; then
    echo -e "${YELLOW}📦 PHP CLIコンテナを起動中...${NC}"
    docker-compose --profile tools up -d php-cli
fi

echo -e "${YELLOW}1. PHP構文チェック${NC}"
docker-compose exec php-cli sh -lc 'find . -name "*.php" -not -path "./vendor/*" -not -path "./.test-runtime/*" -print0 | xargs -0 -n1 php -l'

echo -e "${YELLOW}2. Composer検証${NC}"
docker-compose exec php-cli composer validate --no-check-publish

echo -e "${YELLOW}3. 依存関係インストール${NC}"
docker-compose exec php-cli composer install

echo -e "${YELLOW}4. PHP CS Fixerチェック${NC}"
docker-compose exec php-cli composer format:check

echo -e "${YELLOW}5. PHP CodeSniffer実行${NC}"
docker-compose exec php-cli vendor/bin/phpcs .

echo -e "${YELLOW}6. PHPStan実行${NC}"
docker-compose exec php-cli vendor/bin/phpstan analyse --memory-limit=512M

echo -e "${YELLOW}7. PHPUnit実行${NC}"
docker-compose exec php-cli vendor/bin/phpunit --configuration phpunit.xml.dist

echo -e "${YELLOW}8. バージョン同期テスト${NC}"
docker-compose exec php-cli php scripts/test-version.php

echo -e "${YELLOW}9. 設定ファイルテスト${NC}"
docker-compose exec php-cli php -l config/config.php.example
if docker-compose exec php-cli test -f "config/config.php"; then
    docker-compose exec php-cli php -l config/config.php
fi

echo -e "${GREEN}✅ すべてのチェックが完了しました！${NC}"
