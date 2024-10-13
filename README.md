### Initial Setup

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
REDIS_CACHE_DURATION=60

# other vars
ALPHA_VANTAGE_API_URL=https://www.alphavantage.co/query
ALPHA_VANTAGE_API_KEY=I96SA21INZCRDLAR
```
- Install composer dependencies  
``` $ docker-compose run --rm composer install ```

- Set correct permissions and ownership, if any write errors 
``` $ docker-compose exec app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache && docker-compose exec app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache```

- install Laravel Sanctum for api  
```$ docker-compose exec app php artisan install:api```

- build and start all docker services
```$ docker-compose up -d --build```

- start all docker services  
```$ docker-compose up -d ```

- verify that all services are up  
```$ docker-compose ps```

- start the scheduler  
```$ docker-compose exec app php artisan schedule:work```

### Test that the application is running
```http://127.0.0.1:8082/```  
```http://127.0.0.1:8082/test```  
```http://127.0.0.1:8082/redis-test```
```http://127.0.0.1:8082/api/stock/report/symbol/AAPL```
```http://127.0.0.1:8082/api/stock/report/all```
```$ curl http://127.0.0.1:8082/api/stock/IBM```


### Documentation

#### Dockerizition
All services will be dockerized, using a combination of Dockerfile and docker-compose. I will use different ports for http and mysql than the default, to avoid conflicts with existing host services. The main reasoning for using docker is to have a uniform deployment of the project in any machine or OS.

#### Retrieve the data at regular intervals
We are asked to implement an automated mechanism to fetch the stock price data at regular intervals (e.g: every 1 minute). For this reason I create a new command/CallAlphaVantageApi and used the command schedule in routes/console.php to run it in intervals. I think the command scheduler is a very nice high level alternative of the traditional linux cron jobs, and also the commands can be under source control, which will help us avoid mistakes on server setup.

The ```/Console/Commands/CallAlphaVantageApi.php``` in performing various error handling, due to many issues that may come up on consuming a third party api. I check in turn for any networking issues or invalid urs, for http return status code, for any 'Information' in response which usually is about rate limits being reached, and for actual quote structure to be valid before doing any processing.

#### Endpoint to fetch the latest stock price
To get the latest stock price from the cache I implemented api /stock/{symbol}. This has a fallback to retrieve the data from the database, if for any reason they are not in cache( maybe expired already). In such case, the data is inserted in the cache. We can unit test the api using:  
```curl http://127.0.0.1:8082/api/stock/IBM```

#### Database Design
For storing the data I defined table Quotes. 
Considering that we may have very frequent inserts of data, the table can grow very big, making retrieval slow. To optimize it for efficient retrieval of stock data, I have added an index on 'symbol' attribute.

Now we have 10 stocks, but this number can grow to much more. As a result I'm using the Laravel feature of batch insert the data, to minimize database transactions.

As the database grows we can consider archiving old data, that are no longer needed for real-time processing 

#### Caching - Redis
We are also asked to implement caching to store the latest stock price. We can implement in-memory caching using Redis, which integrates well with Laravel. In the future, we can also consider batch inserts in Redis, using pipelines if stocks become too many.

#### Testing - debugging
For better debugging I have added both console logs and file log messages. Serious issues as marked as 'error' to draw our attention.
Laravel has build-in support with PHPUnit. We will write both Unit tests and Feature tests for our application. We need to cover:
1. unit tests
2. feature tests
3. database tests
4. redis cache tests


### Useful commands for troubleshooting
- Run all tests  
```$ docker-compose exec app php artisan test```

- Test-connect to the database from the host machine  
```mysql -h 127.0.0.1 -P 3308 -u laravel -p```

- Run command manually  
```$ docker-compose exec app php artisan app:call-alpha-vantage-api```

- Clear config cache - in case .env variable are not accessible  
```docker-compose exec app php artisan config:clear&&docker-compose exec app php artisan cache:clear``` 

- Ensure that the docker-compose.yml file is correct  
```docker-compose config```

- access redis  
```docker-compose exec redis redis-cli```  
-- KEYS * (see all keys)  
-- GET key_name  

- Check if Laravel is reading env variables  
```docker-compose exec app php artisan tinker```  
```$ env('APP_NAME');```

- ```$ docker --version```  
Docker version 26.1.4, build 5650f9b
-  ```docker-compose --version```  
docker-compose version 1.29.2, build unknown
