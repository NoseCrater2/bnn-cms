#!/bin/sh

set -e

echo "Configurando replicación MySQL..."

mysql \
    -h mysql-read \
    -uroot \
    -proot \
    -e "STOP REPLICA;" \
    2>/dev/null || true

mysql \
    -h mysql-read \
    -uroot \
    -proot \
    -e "
        CHANGE REPLICATION SOURCE TO
            SOURCE_HOST = 'mysql-write',
            SOURCE_PORT = 3306,
            SOURCE_USER = 'replicator',
            SOURCE_PASSWORD = 'replica_secret',
            SOURCE_AUTO_POSITION = 1,
            GET_SOURCE_PUBLIC_KEY = 1;

        START REPLICA;
    "

echo "Verificando estado..."

mysql \
    -h mysql-read \
    -uroot \
    -proot \
    -e "SHOW REPLICA STATUS\G"

echo "Replicación iniciada."
