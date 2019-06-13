<?php

use SimpleS3\Components\Cache\RedisCache;

require __DIR__.'/../vendor/autoload.php';

$config = parse_ini_file(__DIR__.'/../config/credentials.ini');
$s3Client = new \SimpleS3\Client(
    $config['ACCESS_KEY_ID'],
    $config['SECRET_KEY'],
    [
        'version' => $config['VERSION'],
        'region' => $config['REGION'],
    ]
);

// Inject Cache
$redis = new Predis\Client();
$cacheAdapter = new RedisCache($redis);
$s3Client->addCache($cacheAdapter);

$n = 10;
$x = microtime(true);

echo 'CREATE A BUCKET' . PHP_EOL;
echo '----------------------------------------' . PHP_EOL;
$s3Client->createBucketIfItDoesNotExist(['bucket' => 'mauretto78-benchmark-test-bucket']);
echo 'ELAPSED TIME:' . (microtime(true) - $x) . PHP_EOL . PHP_EOL;

$x = microtime(true);
echo 'UPLOAD '. $n . ' ITEMS IN THE BUCKET' . PHP_EOL;
echo '----------------------------------------' . PHP_EOL;

for ($i=0;$i<$n;$i++) {
    $rand = rand(11111111, 99999999);
    $body = 'lorem ipsum';

    $s3Client->uploadItemFromBody([
        'bucket' => 'mauretto78-benchmark-test-bucket',
        'key' => 'folder/'.$rand . '.txt',
        'body' => $body
    ]);
}

echo 'ELAPSED TIME:' . (microtime(true) - $x) . PHP_EOL. PHP_EOL;

$x = microtime(true);
echo 'GET '. $n . ' ITEMS IN THE BUCKET' . PHP_EOL;
echo '----------------------------------------' . PHP_EOL;

$s3Client->getItemsInABucket([
    'bucket' => 'mauretto78-benchmark-test-bucket',
    'prefix' => 'folder/',
]);

echo 'ELAPSED TIME:' . (microtime(true) - $x) . PHP_EOL. PHP_EOL;

$x = microtime(true);
echo 'DELETE FOLDER IN THE BUCKET' . PHP_EOL;
echo '----------------------------------------' . PHP_EOL;

$s3Client->deleteFolder([
    'bucket' => 'mauretto78-benchmark-test-bucket',
    'prefix' => 'folder/',
]);

echo 'ELAPSED TIME:' . (microtime(true) - $x) . PHP_EOL. PHP_EOL;

$x = microtime(true);
echo 'DELETE BUCKET' . PHP_EOL;
echo '----------------------------------------' . PHP_EOL;

$s3Client->deleteBucket([
    'bucket' => 'mauretto78-benchmark-test-bucket',
]);

echo 'ELAPSED TIME:' . (microtime(true) - $x) . PHP_EOL. PHP_EOL;
