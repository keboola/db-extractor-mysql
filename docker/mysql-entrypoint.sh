#!/bin/bash

if [[ "$MYSQL_VERSION" =~ ^5\.6 ]]; then
  exec docker-entrypoint.sh mysqld --local-infile --port=3306 --default-authentication-plugin=mysql_native_password
elif [[ "$MYSQL_VERSION" =~ ^9.[0-1] ]]; then
  exec docker-entrypoint.sh mysqld --local-infile --port=3306 --upgrade=FORCE
else
  exec docker-entrypoint.sh mysqld --local-infile --port=3306 --mysql-native-password=ON
fi
