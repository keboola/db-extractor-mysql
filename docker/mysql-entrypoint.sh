#!/bin/bash

if [[ "$MYSQL_VERSION" =~ ^5\.6 ]]; then
  exec docker-entrypoint.sh mysqld --local-infile --port=3306 --default-authentication-plugin=mysql_native_password
else
  exec docker-entrypoint.sh mysqld --local-infile --port=3306 --upgrade=FORCE --caching-sha2-password-auto-generate-rsa-keys=ON
fi
