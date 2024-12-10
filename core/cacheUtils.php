<?php
define('CACHE_DIR', __DIR__ . '/../cache/'); // Path to the cache directory
define('CACHE_TIME', 300); // Cache duration in seconds

// Function to get data from cache
function getFromCache($key) {
    $cacheFile = CACHE_DIR . md5($key) . '.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < CACHE_TIME) {
        return json_decode(file_get_contents($cacheFile), true);
    }
    return null;
}

// Function to save data to cache
function saveToCache($key, $data) {
    $cacheFile = CACHE_DIR . md5($key) . '.json';
    file_put_contents($cacheFile, json_encode($data));
}
