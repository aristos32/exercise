<?php echo "Hello World";

$stocks = config('alphaVantage.stocks');
print_r($stocks);

// time series
$json = file_get_contents(env('ALPHA_VANTAGE_API_URL') . '?function=TIME_SERIES_INTRADAY&symbol=IBM&interval=5min&apikey=demo');
$data = json_decode($json, true);

echo "<pre>";
print_r($data);
echo "</pre>";

// quote
$json = file_get_contents(env('ALPHA_VANTAGE_API_URL') . '?function=GLOBAL_QUOTE&symbol=IBM&&apikey=' . env('ALPHA_VANTAGE_API_KEY'));
$data = json_decode($json, true);

echo "<pre>";
print_r($data);
echo "</pre>";

// real time options
$json = file_get_contents(env('ALPHA_VANTAGE_API_URL') . '?function=REALTIME_OPTIONS&symbol=IBM&&apikey=' . env('ALPHA_VANTAGE_API_KEY'));
$data = json_decode($json, true);

echo "<pre>";
print_r($data);
echo "</pre>";

Log::debug('An informational message.');

exit;