<?php
//配置文件
return [
    'APPID_ACCOMPANY'     => 'wxecd6bfdba0623aa5', // 陪玩端 appid
    'APPSECRET_ACCOMPANY' => '6478992f651e85bb966b8fd5db9c8771', // 小程序 appSecret
    'MD5_PARAM'           => 'douzhikeji',
    'SDKAPPID'            => '1400158369',
    'APPKEY'              => 'de4357e1435d09cb5880a2e1d4a22273', // 发短信用的 App 凭证
    'LOWERMONEY'          => 35, // 订单可使用优惠卷下限
    'COUPONTERM'          => 15, // 优惠卷期限
    'RATIO'               => 1, //陪玩师分钱比例
    'KG'                  => [
        ['para_id' => 5, 'para_str' => '全段位'],
        ['para_id' => 4, 'para_str' => '王者21星以下'],
        ['para_id' => 3, 'para_str' => '王者以下'],
        ['para_id' => 2, 'para_str' => '星耀以下'],
        ['para_id' => 1, 'para_str' => '我是娱乐陪玩，不提供包赢服务'],
    ],
    'CJ'                  => [
        ['para_id' => 2, 'para_str' => '游戏辅导'],
        ['para_id' => 1, 'para_str' => '寂寞陪玩'],
    ],
];
