<?php

/**
 * @Author: smile
 * @Date:   2018-08-28 18:06:02
 * @Last Modified by:   smile
 * @Last Modified time: 2018-08-29 17:25:22
 * 1.redis存储user_id与fd的映射关系
 * 2.限制访问IP
 * 3.踢下线
 *
 *
 * 4.再测试异步、协程处理
 */

/**
 * 存储用户映射
 */
// include './config.php';
include './cache.php';
class Save 
{

    private static $cache;
    private static $config;

    function __construct($config)
    {
    	if (!self::$cache) {
    		self::$cache = Cache::getInstance($config);
    	}
    	if (!self::$config) {
    		self::$config = $config;
    	}
    }
    
    //1. 将user_id注册，返回给用户端 sid   存储 user_id <=>sid
    //2. 链接成功，将fd与sid映射    fd<=>sid 存储
    //3. 推送消息接口 通过 user_id =>sid =>fd  发送消息
	public function regist($user_id)
	{
		$sid = md5($user_id.time()); 
		$this->deluser($user_id);
		$res1 = $this->saveUserSid($user_id,$sid);
		$res2 = $this->saveSidUser($sid,$user_id);

		if ($res1 && $res2) {
			return json_encode(['message'=>'success','code'=>0,'data'=>$sid]);
		}else{
			return json_encode(['message'=>'fail','code'=>1]);
		}
	}

    /**
     * 登录操作  验证ip  验证sid是否有效,简历 user_id->fd
     * @param  [type] $sid [description]
     * @param  [type] $fd  [description]
     * @return [type]      [description]
     */
	public function login($sid,$fd,$ip)
	{
       $user_id = $this->getUserbySid($sid);
       if (empty($user_id)) {
       	 return false;
       }
       if (!$this->checkIP($ip)) {
       	  return false;
       }
       $res = $this->saveUserFd($user_id,$fd);
       $res1 = $this->saveFdUser($fd,$user_id);
       if ($res && $res1) {
       	    return true;
       }else{
        	return false;
       }	
	}
    
    /*
      推送信息by用户信息
     */
	public function pushByUser($user_id)
	{
		return $this->getFdbyUser($user_id);
	}
	


	
	public function checkIP($ip)
	{
       if (in_array($ip,self::$config['platform']['ip'])) {
       	  return true;
       }
       return false;
	}
    
  
    
    /**
     * 根据user_id获取sid信息
     * @param  [type] $sid [description]
     * @return [type]      [description]
     */
	public function getSidByUser($user_id)
	{
		return self::$cache->hget('user_sid',$user_id);
	}
	
	public function getUserByFd($fd)
	{
		return self::$cache->hget('fd_user',$fd);
	}
    
    /**
     * 根据sid获取fd客户端链接序号
     * @param  [type] $sid [description]
     * @return [type]      [description]
     */
	public function getFdbyUser($user_id)
	{
		return self::$cache->hget('user_fd',$user_id);
	}

	public function getUserbySid($sid)
	{
		return self::$cache->hget('sid_user',$sid);
	}

	/**
     * sid与user_id
     * @return [type] [description]
     */
	public function saveUserFd($user_id,$fd)
	{
		return self::$cache->hset('user_fd',$user_id,$fd);
	}
    
    /*
      fd 与user_id 映射
     */
	public function saveFdUser($fd,$user_id)
	{
		return self::$cache->hset('fd_user',$fd,$user_id);
	}
    
    /**
     * 存储用户与sid的映射
     * @param  [type] $user_id [description]
     * @param  [type] $sid     [description]
     * @return [type]          [description]
     */
	public function saveUserSid($user_id,$sid)
	{
		return self::$cache->hset('user_sid',$user_id,$sid);
	}
    
    /**
     * 存储sid与用户的映射
     * @param  [type] $sid     [description]
     * @param  [type] $user_id [description]
     * @return [type]          [description]
     */
	public function saveSidUser($sid,$user_id)
	{
		return self::$cache->hset('sid_user',$sid,$user_id);
	}
    
    /**
     * 删除user_sid
     * @return [type] [description]
     */
	public function deluser($user_id)
	{
	   if (self::$cache->hexists('user_sid',$user_id)) {
	   	   $sid = $this->getSidByUser($user_id);
	   	   $fd = $this->getFdbyUser($user_id);
	   	   self::$cache->hdel('user_fd',$user_id);
		   self::$cache->hdel('sid_user',$sid);  //删除sid->user
		   self::$cache->hdel('user_sid',$user_id);//删除user->sid
		   self::$cache->hdel('fd_user',$fd);
		}
		return true;
	}

    /**
     * 删除
     * @param  string $value [description]
     * @return [type]        [description]
     */
	public function delUserByFd($fd)
	{
       if (self::$cache->hexists('fd_user',$fd)) {
       	   $user_id = $this->getUserByFd($fd);
	   	   $sid = $this->getSidByUser($user_id);
	   	   self::$cache->hdel('user_fd',$user_id);
		   self::$cache->hdel('sid_user',$sid);  //删除sid->user
		   self::$cache->hdel('user_sid',$user_id);//删除user->sid
		   self::$cache->hdel('fd_user',$fd);
		}
		return true;
	}

    	
}