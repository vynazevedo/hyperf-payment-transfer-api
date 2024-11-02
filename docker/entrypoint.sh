#!/bin/sh

set -e

until php -r "
try {
    new PDO('mysql:host=${DB_HOST};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');
    echo 'MySQL is ready.\n';
    exit(0);
} catch (PDOException \$e) {
    echo 'MySQL is not ready: ' . \$e->getMessage() . '\n';
    exit(1);
}
"
do
    echo "Waiting for MySQL..."
    sleep 1
done

until php -r "
try {
    \$redis = new Redis();
    \$redis->connect('${REDIS_HOST}', ${REDIS_PORT});
    \$redis->ping();
    echo 'Redis is ready.\n';
    exit(0);
} catch (Exception \$e) {
    echo 'Redis is not ready: ' . \$e->getMessage() . '\n';
    exit(1);
}
"
do
    echo "Waiting for Redis..."
    sleep 1
done

php bin/hyperf.php migrate
php bin/hyperf.php start