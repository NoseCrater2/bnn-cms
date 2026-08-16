CREATE USER IF NOT EXISTS
    'replicator'@'%'
    IDENTIFIED WITH caching_sha2_password
    BY 'replica_secret';

GRANT REPLICATION SLAVE
ON *.*
TO 'replicator'@'%';

FLUSH PRIVILEGES;
