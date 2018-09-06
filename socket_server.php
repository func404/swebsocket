<?php
/**
 * 启动socket服务
 */
include './config.php';
include './save.php';
class WebsocketTest {
    // public  $server;
    private static $save;
   
    function __construct($config) {
        if (!self::$save) {
           self::$save = new Save($config);
        }
        $this->server = new swoole_websocket_server($config['socket']['host'], $config['socket']['port']);
        //new swoole_websocket_server("0.0.0.0", Config::SOCKET_PORT,SWOOLE_BASE,SWOOLE_SOCK_TCP | SWOOLE_SSL);
        $this->server->set($config['socket']['option']);
        $this->server->on('open', function (swoole_websocket_server $server, $request) {
            // echo "server: handshake success with fd{$request->fd}";
            $res = self::$save->login($request->get['sid'],$request->fd,$request->server['remote_addr']);
            if ($res) {
               $server->push($request->fd, json_encode(['message'=>"server: handshake success ,链接成功",'code'=>0])); //开始 
            }else{
                $server->close($request->fd);
            }
        });
        $this->server->on('message', function (swoole_websocket_server $server, $frame) {
            // echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
            $server->push($frame->fd, "this is server");
        });
        $this->server->on('close', function ($ser, $fd) {
            // echo "client {$fd} closed\n";
            self::$save->delUserByFd($fd);
        });
        $this->server->on('request', function ($request, $response) {

            // 接收http请求从get获取message参数的值，给用户推送
            // $this->server->connections 遍历所有websocket连接用户的fd，给所有用户推送
            if ($request->server['request_uri'] == '/regist') {  //注册接口

                $user_id = $request->get['user_id'].$request->get['platform'];
                $response->end(self::$save->regist($user_id));

            }elseif ($request->server['request_uri'] == '/push') {
                $str = "";
                if (!empty($request->get)) {
                   $str = "get";
                }elseif (!empty($request->post)) {
                   $str = "post";
                }else{
                  $str = "request";
                }
                $user_id = $request->$str['user_id'].$request->$str['platform'];
                $data = $request->$str['data'];
                $fd = self::$save->pushByUser($user_id);
                if ($fd) {
                  $this->server->push($fd, $data); //开始 
                }
                
            }elseif ($request->server['request_uri'] == '/close') {
                $str = '';
                if (!empty($request->get)) {
                   $str = 'get';
                }elseif (!empty($request->post)) {
                   $str = 'post';
                }else{
                  $str = 'request';
                }
                $user_id = $request->$str['user_id'].$request->$str['platform'];
                $fd = self::$save->getFdbyUser($user_id);
                $this->server->close($fd);
                self::$save->deluser($user_id);
            }
        });
        $this->server->start();
    }
}
new WebsocketTest($config);

/**
 * fd映射到user_id
 */
