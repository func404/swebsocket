<?php

/**
 * @Author: smile
 * @Date:   2018-08-29 11:12:21
 * @Last Modified by:   smile
 * @Last Modified time: 2018-08-29 18:04:30
 */
return $config = [

  'redis'=>[
    'host'=>'127.0.0.1',
    'port'=>'6379',
    'auth'=>''
  ],

  'socket'=>[
     'host'=>'0.0.0.0',
     'port'=>9501,
     'option'=>[
            'websocket_subprotocol' => 'swoole_chat',
            'reactor_num'=>2*2,
            'worker_num'=>2,
            'max_connection'=>256,
            'daemonize' => true,
            'backlog'=>256
        ],
  ],

  'platform'=>[
     'ip'=>['127.0.0.1']

  ]




];