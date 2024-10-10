### Run these commands initially

Install composer dependencies  
``` (main)$ docker-compose run --rm composer install ```

Set correct permissions  
``` (main)$ docker-compose exec app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache ```  

Set correct ownership  
``` (main)$ docker-compose exec app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache```

create .env file  
```cp my-laravel/.env.example my-laravel/.env```

update these database variable to match the ones in the docker-compose  
```
# db
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=password

# other vars
API_URL=https://www.alphavantage.co/query
ALPHA_VANTAGE_API_KEY=I96SA21INZCRDLAR
```

connect to the database from the host machine  
```mysql -h 127.0.0.1 -P 3308 -u laravel -p```

### To view the application
```http://127.0.0.1:8082/  ```