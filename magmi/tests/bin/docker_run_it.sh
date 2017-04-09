#!/bin/bash

MAGE2_FAKE_URL=http://127.0.0.1 \
    MAGE2_ADMIN_USERNAME=admin \
    MAGE2_ADMIN_PASSWORD=admin123 \
    MAGE2_DB_HOST=db \
    MAGE2_DB_USER=root \
    MAGE2_DB_PASS=root \
    MAGE2_DB_NAME=magento2 \
    php tests/bin/phpunit.phar -c tests/integration/phpunit.xml