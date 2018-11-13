<?php
$serv = new swoole_websocket_server("0.0.0.0", 3999, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
//服务的基本设置
$serv->set(array(
    'worker_num'               => 2,
    'reactor_num'              => 8,
    'task_worker_num'          => 1,
    'dispatch_mode'            => 2,
    'debug_mode'               => 1,
    'daemonize'                => true,
    'log_file'                 => __DIR__ . '/log/webs_swoole.log',
    'heartbeat_check_interval' => 60,
    'heartbeat_idle_time'      => 600,
    'ssl_cert_file'            => '/root/swoole-src/tests/include/api/swoole_http_server/localhost-ssl/server.pem',
    // 'ssl_cert_file'            => '/root/swoole-src/examples/ssl/ssl.crt',
    // 'ssl_cert_file'            => '/usr/local/src/swoole/ca/server/server.crt',
    'ssl_key_file'             => '/root/swoole-src/examples/ssl/ssl.key',
    // 'ssl_key_file'             => '/usr/local/src/swoole/ca/server/server.key',
));

$serv->on('connect', function ($serv, $fd) {
    echo "client:$fd Connect." . PHP_EOL;
});

//测试receive
$serv->on("receive", function (swoole_server $serv, $fd, $from_id, $data) {
    echo "receive#{$from_id}: receive $data " . PHP_EOL;
});

$serv->on('open', function ($server, $req) {
    echo "server#{$server->worker_pid}: handshake success with fd#{$req->fd}" . PHP_EOL;;
    // file_put_contents('/www/wwwroot/wwwdragontangcom/swoole.log', $server->worker_pid.',', FILE_APPEND);
});

$serv->on('message', function ($server, $frame) {
    // $msg = json_decode($frame->data, true);
    $msg = json_decode($frame->data, true);
    // $str = json_encode($frame);
    // file_put_contents('/www/wwwroot/wwwdragontangcom/swoole.log', json_encode($msg), FILE_APPEND);
    if ($msg['type'] === 'login') {
        $path        = '/root/swoole-src/users/' . date('Ymd') . $msg['roomid'] . '.log';
        $content_arr = ['roomid' => $msg['roomid'], 'users' => []];
        if (!file_exists($path)) {
            @fopen($path, 'w+');
        } else {
            $content = file_get_contents($path);
        }
        if (!empty($content)) {
            $content_arr = json_decode($content, true);
        }
        array_push($content_arr['users'], $frame->fd);
        file_put_contents($path, json_encode($content_arr));
    }
    $msg['fd'] = $frame->fd;
    // $server->task($msg);
    $server->task($frame->data);
});

$serv->on("workerstart", function ($server, $workerid) {
    echo "workerstart: " . $workerid . PHP_EOL;
});

$serv->on("task", "on_task");

$serv->on("finish", function ($serv, $task_id, $data) {
    return;
});
$serv->on('close', function ($server, $fd, $from_id) {
    echo "connection close: " . $fd;
});

$serv->start();
function on_task($serv, $task_id, $from_id, $data)
{
    // file_put_contents('/www/wwwroot/wwwdragontangcom/swoole.log', $data.'|', FILE_APPEND);
    $data_arr        = explode('|', $data);
    $msg_arr['stat'] = 'OK';
    $str_arr         = explode('=', $data_arr[0]);
    $type            = $str_arr[0];
    $msg_arr['type'] = $type;

    $data    = json_decode($data, true);
    $userstr = file_get_contents('/root/swoole-src/users/' . date('Ymd') . $data['roomid'] . '.log');
    $users   = json_decode($userstr, true);
    foreach ($users['users'] as $conn) {
        $serv->push($conn, json_encode($msg_arr));
    }
    return;
}
function on_finish($serv, $task_id, $data)
{
    return true;
}
