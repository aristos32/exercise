### Run these commands initially

Install composer dependencies  
- docker-installation/my-laravel (main)$ docker-compose run --rm composer install

Set correct permissions>  
- docker-compose exec app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

Set correct ownership  
- docker-compose exec app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

### To view the application
http://127.0.0.1:8082/  