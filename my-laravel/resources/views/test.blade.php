<?php echo "Hello World";

$stocks = config('alphaVantage.stocks');
print_r($stocks);

$json = file_get_contents(env('API_URL') . '?function=TIME_SERIES_INTRADAY&symbol=IBM&interval=5min&apikey=demo');

$data = json_decode($json, true);

print_r($data);

exit;