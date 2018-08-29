<?php

/**
 * @Author: smile
 * @Date:   2018-08-29 11:02:28
 * @Last Modified by:   smile
 * @Last Modified time: 2018-08-29 12:01:17
 */
/**
 * 缓存redis
 */

class Cache extends Redis
{
	private static $redis;

	public static function getInstance($config)
	{
		if (!self::$redis) {
			self::$redis = new self();
            self::$redis->pconnect($config['redis']['host'], $config['redis']['port']);
            if (!empty($config['redis']['auth'])) {
            	self::$redis->auth($config['redis']['auth']);
            }
           return self::$redis;
		} 
	}
}

//  $cache = Cache::getInstance($config);

// var_dump($cache->keys('*'));