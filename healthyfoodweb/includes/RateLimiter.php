<?php

require_once __DIR__ . '/../vendor/autoload.php';

function check_rate_limit($ip, $limit = 120, $window = 60)
{
    try {
        $redis = new Predis\Client([
            'scheme' => 'tcp',
            'host' => '127.0.0.1',
            'port' => 6379,
        ]);

        $key = "login_attempt:$ip";
        $current = $redis->get($key);

        if ($current !== null && $current >= $limit) {
            return true;
        }

        if ($current === null) {
            $redis->setex($key, $window, 1);
        } else {
            $redis->incr($key);
        }

        return false;
    } catch (Exception $e) {
        die("<h3>Redis Rate Limiter Error</h3><p>" . $e->getMessage() . "</p>");
    }
}

function record_login_failure($identifier, $window = 3600)
{
    try {
        $redis = new Predis\Client([
            'scheme' => 'tcp',
            'host' => '127.0.0.1',
            'port' => 6379,
        ]);

        $key = "brute_force:$identifier";
        $count = $redis->incr($key);

        if ($count === 1) {
            $redis->expire($key, $window);
        }

        return $count;
    } catch (Exception $e) {
        die("<h3>Redis Brute Force Tracker Error</h3><p>" . $e->getMessage() . "</p>");
    }
}
