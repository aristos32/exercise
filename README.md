### Initial Setup

- Set correct permissions and ownership 
``` (main)$ docker-compose exec app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache && docker-compose exec app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache```

- create .env file  
```cp my-laravel/.env.example my-laravel/.env```

- update these database variable to match the ones in the docker-compose  
```
# db
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=password

# redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# other vars
API_URL=https://www.alphavantage.co/query
ALPHA_VANTAGE_API_KEY=I96SA21INZCRDLAR
```
- Install composer dependencies  
``` (main)$ docker-compose run --rm composer install ```

- start all docker services  
``` docker-compose up -d ```

- Test-connect to the database from the host machine  
```mysql -h 127.0.0.1 -P 3308 -u laravel -p```

- Run command manually  
```$ docker-compose exec app php artisan app:call-alpha-vantage-api```

- Clear config cache - in case .env variable are not accessible  
```docker-compose exec app php artisan config:clear&&docker-compose exec app php artisan cache:clear```  

### To view the application
```http://127.0.0.1:8082/```  
```http://127.0.0.1:8082/redis-test```

### Documentation
#### Architecture Decisions

We are asked to implement an automated mechanism to fetch the stock price data at regular intervals (e.g:
every 1 minute). This leads us to use Laravel's Command for the logic, as well as the kernel scheduler for the repetition every minute. For this reason I create a new command/CallAlphaVantageApi and updated the app/Console/Kernel.php to schedule your command

We are also asked to implement caching to store the latest stock price. We can implement in-memory caching using Redis, which integrates well with Laravel.

All services needed will be dockerized, using a combination of Dockerfile and docker-compose. I will use different ports for http and mysql, to avoid conflicts with existing host services.

### Useful commands
- docker-compose config - Ensure that the docker-compose.yml file is correct
- docker-compose exec redis redis-cli - access redis  
-- KEYS * (see all keys)  
-- GET key_name  
- Check if Laravel is reading env variables  
docker-compose exec app php artisan tinker  
$ env('APP_NAME');  

