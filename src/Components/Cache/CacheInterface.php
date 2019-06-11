<?php

namespace SimpleS3\Components\Cache;

interface CacheInterface
{
    public function set($bucket, $keyname, $content, $ttl = null);

    public function get($bucket, $keyname);

    public function remove($bucket, $keyname);

    public function search($bucket, $keyname = null);
}