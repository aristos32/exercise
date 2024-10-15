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

- If any permission errors errors like this occurs:  
```The stream or file "/var/www/html/storage/logs/laravel.log" could not be opened in append mode: Failed to open stream: Permission denied The exception occurred while attempting to log```  
then set correct permissions and ownership:  
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
```http://127.0.0.1:8082/api/stock/get/AAPL```
```http://127.0.0.1:8082/api/stock/report/AAPL```
```http://127.0.0.1:8082/api/stock/report```
```$ curl http://127.0.0.1:8082/api/stock/IBM```


### Documentation

#### General
I have registered for a free api key, which has limited amount of daily requests. It is provided above, to be added in the .env file.

The most related endpoint I found was the Quote Endpoint api https://www.alphavantage.co/documentation/#latestprice, which returns the latest price and volume information for a selected ticker. But as it stated in the documentation:  
```Tip: by default, the quote endpoint is updated at the end of each trading day for all users. If you would like to access realtime or 15-minute delayed stock quote data for the US market, please subscribe to a premium membership plan for your personal use. For commercial use, please contact sales.```  
As such, we cannot have any meaningful percentage changes during the day, as all prices will be the same as the end of the previous day. A workaround can be to call our scheduler on daily basis, and so we will get the percentage change between 2 days. 

However when we have a proper api, or a paid subsription, the architecture will apply, and with minimal changes on api, variable names, and database table columns, the reporting system will work fine.

#### Dockerization
All services were dockerized, using a combination of Dockerfile and docker-compose. I have used different ports for http and mysql than the default, to avoid conflicts with existing host services. The main reasoning for using docker is to have a uniform deployment of the project in any machine or OS.

#### Retrieve the data at regular intervals
It was asked to implement an automated mechanism to fetch the stock price data at regular intervals (e.g: every 1 minute). For this reason I create a new command/CallAlphaVantageApi and used the command schedule in routes/console.php to run it in intervals. I think the command scheduler is a very nice high level alternative of the traditional linux cron jobs, and also the commands can be under source control, which will help us avoid mistakes on server setup.

The ```/Console/Commands/CallAlphaVantageApi.php``` is performing various error handling, due to many issues that may come up on consuming a third party api. I check in turn for any networking issues or invalid urls, for http return status code, for any 'Information' in response which indicates usually rate limits being reached, and for actual quote structure to be valid before doing any processing, which might be related to api internal errors.

#### Endpoint to fetch the latest stock price
This is the quote as we received it from the AlphaVantage Api, without any processing yet.
To get the latest stock price from the cache I implemented api /stock/get/{symbol}. This has a fallback to retrieve the data from the database, if for any reason they are not in cache( maybe expired already). In such case, the data is inserted in the cache. We can unit test the api using:  
```curl http://127.0.0.1:8082/api/stock/get/IBM``` OR  
```http://127.0.0.1:8082/api/stock/get/AAPL```

#### Database Design
For storing the data I defined table Quotes. 
Considering that we may have very frequent inserts of data, the table can grow very big, making retrieval slow. To optimize it for efficient retrieval of stock data, I have added an index on 'symbol' attribute. Now we retrieve data for specific symbol, and from these the latest by id(which is already indexed as a primary key) so this will be fast. Another possible index can be latest_trading_day, but I don't believe it is needed under current specifications. Also we limit the data to latest, which is more network efficient. Example of optimized query:  
```$quote = Quote::where('symbol', $symbol)->latest('id')->first();```

Now we have 10 stocks, but this number can grow to much more. As a result I'm using the Laravel feature of batch insert the data, to minimize database transactions, thus optimizing storing.

As the database grows we can consider archiving old data, that are no longer needed for real-time processing. Another option can be database sharding, and accessing the appropriate shard using application logic. An appropriate field for sharding is ```latest_trading_day```.

#### Caching - Redis
It was also asked to implement caching to store the latest stock price. I implemented in-memory caching using Redis, which integrates well with Laravel. In the future, we can also consider batch inserts in Redis, using pipelines if stocks become too many. However this was not implemented as I consider a round trip to Redis not as costly as a database round-trip in order to batch it in the initial stages of a new project.

The idea of using a cache like this, is the same as in computer processors. 
- If we need to find some data, we first look the cache(RAM). This is very fast, as is in-memory.
- If data are found, we use them, thus reducing signifigantly the calls to the database(disk). The difference can be some orders of magnitude.
- if not found, we get the data from the database, but also update the cache for subsequent retrievals.  

There are various algorithms that invalidate cache, and get fresh data from the database.

#### Testing - debugging
For better debugging I have added both console logs and file log messages. Serious issues as marked as 'error' to draw our attention.
Laravel has build-in support with PHPUnit. I have written both Unit tests and Feature tests for our application. Testing coverage included:
1. manual testing during development
2. unit tests added in tests/unit
3. feature tests added in tests/
4. database tests are tested as part of feature testing
5. redis cache tests are tested as part of feature testing

To avoid accidental cases of modifying the database during testing we used the ```use RefreshDatabase; trait```, as well as an in-memory sqlite option of phpUnit.xml with below params:  
```<env name="DB_CONNECTION" value="sqlite"/>```  
```<env name="DB_DATABASE" value=":memory:"/>```

### Bonus - user interface with latest stock price
This can be implemented using websockets. Laravel has good support for this using a server-side broadcasting driver that broadcasts the events, and Laravel Echo(a frontend Javascript library) can receive them within the browser client. I didn't implement this part due to lack of time.

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
