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
    'ssl_cert_file'            => '/usr/local/src/swoole/ca/server/server.crt',
    'ssl_key_file'             => '/usr/local/src/swoole/ca/server/server.key',
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
});

$serv->on('message', function ($server, $frame) {
    $msg = json_decode($frame->data, true);
    $str = json_encode($frame);
    // file_put_contents('/www/wwwroot/wwwdragontangcom/swoole.log', $str);
    if ($msg['type'] === 'login') {
        $path        = '/root/swoole-src/users/' . date('Ymd') . $msg['roomid'].'.log';
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
    $data_arr        = explode('|', $data);
    $msg_arr['stat'] = 'OK';
    $str_arr         = explode('=', $data_arr[0]);
    $type            = $str_arr[0];
    $umsg_arr        = array();
    $msg_arr['type'] = $type;
    if ($type == 'SendMsg') {
        $umsg_arr['ChatId']   = 'x0714679E';
        $umsg_arr['ToChatId'] = $str_arr[2];
        if ($str_arr[2] != 'ALL') {
            $umsg_arr['IsPersonal'] = true;
        } else {
            $umsg_arr['IsPersonal'] = false;
        }
        $umsg_arr['Style'] = $data_arr[2];
        $umsg_arr['Txt']   = $data_arr[3];
        $type              = 'UMsg';
    } else {
        $umsg_arr['roomid'] = 1;
        $umsg_arr['chatid'] = 'x0714A466';
        $umsg_arr['nick']   = '游客0714A466';
        $umsg_arr['sex']    = 0;
        $umsg_arr['age']    = 0;
        $umsg_arr['qx']     = 0;
        $umsg_arr['ip']     = '59.175.39.166';
        $umsg_arr['vip']    = '繁华落尽';
        $umsg_arr['color']  = 0;
        $umsg_arr['cam']    = 0;
        $umsg_arr['state']  = 0;
        $umsg_arr['mood']   = '';
    }

    $data = json_decode($data, true);
    $userstr = file_get_contents('/root/swoole-src/users/' . date('Ymd') . $data['roomid'].'.log');
    $users = json_decode($userstr, true);
    foreach ($users['users'] as $conn) {
        if (in_array($conn, $users['users'])) {
            $serv->push($conn, json_encode($msg_arr));
        }
    }
    return;
}
function on_finish($serv, $task_id, $data)
{
    return true;
}
