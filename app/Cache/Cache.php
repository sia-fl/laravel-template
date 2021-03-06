<?php

namespace App\Cache;

use App\Exceptions\BurstException;
use Illuminate\Support\Facades\Redis;

class Cache
{
    private static function select($num)
    {
        $client = Redis::client();
        $ok     = $client->select($num);
        if (!$ok) {
            throw new BurstException('服务器内部链接失败 r');
        }
        $client->setOption(1, 1);
        return $client;
    }

    static function permissionGroup()
    {
        return self::select(15);
    }

    static function userCan()
    {
        return self::select(14);
    }

    static function pageConfig()
    {
        return self::select(13);
    }

    static function dataCache()
    {
        return self::select(12);
    }
}
