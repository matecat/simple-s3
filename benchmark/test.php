<?php

use Predis\Client;
use SimpleS3\Components\Cache\RedisCache;

require __DIR__.'/../vendor/autoload.php';

$n = 100000;
$x = microtime(true);
$cache = new RedisCache(new Client());

echo 'SET, GET AND REMOVE '. $n . ' ITEMS' . PHP_EOL;
echo '----------------------------------------' . PHP_EOL;

for ($i=0;$i<$n;$i++){
    $rand = rand(11111111, 99999999);

    $cache->set($i, $rand, '');
    $cache->get($i, $rand);
    $cache->remove($i, $rand);
}

echo 'ELAPSED TIME:' . (microtime(true) - $x) . PHP_EOL;
