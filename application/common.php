<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

function limit()
{
    $start = 'Y-m-d ' . config('START_TIME') . ':00';
    $end   = 'Y-m-d ' . config('END_TIME') . ':00';
    $start = date($start);
    $end   = date($end);
    $start = strtotime($start);
    $end   = strtotime($end);
    if ($start < time() || $end > time()) {
        echo json_encode(['status' => 444, 'info' => "本活动将于" . config('START_TIME') . "-" . config('END_TIME') . "之间开启，点击预约！", 'data' => null]);exit;
    }
}
