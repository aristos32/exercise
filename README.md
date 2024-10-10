# Setup
- docker-installation/my-laravel (main)$ docker-compose run --rm composer install
Set correct permissions

docker-compose exec app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

Set correct ownership

docker-compose exec app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache