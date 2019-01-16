<?php

/**
 * 生成随机字符串
 * @param  integer $num 生成字符串的长度
 * @return string       返回生成的随机字符串
 */
function get_random_str($num = 8)
{
    $pattern = 'AaZzBb0YyCc9XxDd8Ww7EeVvF6fUuG5gTtHhS4sIiRr3JjQqKkP2pLlO1oMmNn';
    $str     = '';
    for ($i = 0; $i < $num; $i++) {
        $str .= $pattern{mt_rand(0, 35)}; //生成 php 随机数
    }
    return $str;
}

/**
 * 生成随机数字串
 * @author 贺强
 * @time   2018-11-05 15:14:37
 * @param  integer $num 要生成数字串的长度
 * @return string       返回生成的数字串
 */
function get_random_num($num = 6)
{
    $pattern = '9482135706';
    $str     = '';
    for ($i = 0; $i < $num; $i++) {
        $str .= $pattern{mt_rand(0, 9)}; //生成 php 随机数
    }
    return $str;
}

/**
 * 把用户状态转换成文字
 * @param  integer $status 用户状态值
 * @return string          返回用户状态
 */
function get_user_status($status = 0)
{
    $status_txt = '';
    switch ($status) {
        case 0:
            $status_txt = '待审核';
            break;
        case 4:
            $status_txt = '审核不通过';
            break;
        case 6:
            $status_txt = '禁用';
            break;
        case 8:
            $status_txt = '正常';
            break;
    }
    return $status_txt;
}

/**
 * 获取毫秒数
 */
function get_millisecond()
{
    list($microsecond, $time) = explode(' ', microtime()); //' '中间是一个空格
    return (float) sprintf('%.0f', (floatval($microsecond) + floatval($time)) * 1000);
}

/**
 * 生成密码
 * @param  string  $str    明文密码
 * @param  string  $salt   密码盐
 * @param  integer $start  截取开始位置
 * @param  integer $length 截取长斋
 * @return string          返回md5加密并截取后的密码
 */
function get_password($str, $salt, $start = 5, $length = 27)
{
    return substr(md5($str . $salt), $start, $length);
}

/**
 * 获取客户端IP
 * @author 贺强
 * @time   2018-11-13 10:05:32
 * @return string 返回获取的IP
 */
function get_client_ip()
{
    // 判断服务器是否允许$_SERVER
    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $realip = $_SERVER['REMOTE_ADDR'];
        }
    } else {
        //不允许就使用getenv获取
        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $realip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_CLIENT_IP')) {
            $realip = getenv('HTTP_CLIENT_IP');
        } else {
            $realip = getenv('REMOTE_ADDR');
        }
    }
    return $realip;
}

/**
 * 数组转 xml
 * @author 贺强
 * @time   2018-11-13 15:03:03
 * @param  array  $arr 被转换的数组
 * @return string      返回转换后的 xml 字符串
 */
function array2xml($arr)
{
    $xml = "<xml>";
    foreach ($arr as $key => $val) {
        $xml .= "<$key>$val</$key>";
    }
    $xml .= "</xml>";
    return $xml;
}

/**
 * xml 转换为数组
 * @author 贺强
 * @time   2018-11-13 15:12:04
 * @param  string $xml 被转换的 xml
 * @return array       返回转换后的数组
 */
function xml2array($xml)
{
    libxml_disable_entity_loader(true);
    $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $values;
}

/**
 * 格式化数字
 * @author 贺强
 * @time   2018-11-30 16:12:39
 * @param  integer $num 要格式化的数字
 * @return string       返回格式化后的字符串
 */
function format_number($num = 0)
{
    if ($num > 100000000) {
        $num /= 100000000;
        return round($num, 2) . '亿';
    } elseif ($num > 10000000) {
        $num /= 10000000;
        return round($num, 2) . '千万';
    } elseif ($num > 1000000) {
        $num /= 1000000;
        return round($num, 2) . '百万';
    } elseif ($num > 100000) {
        $num /= 100000;
        return round($num, 2) . '十万';
    } elseif ($num > 10000) {
        $num /= 10000;
        return round($num, 2) . '万';
    } else {
        return $num;
    }
}

/**
 * 计算两点地理坐标之间的距离
 * @param  decimal $longitude1 起点经度
 * @param  decimal $latitude1  起点纬度
 * @param  decimal $longitude2 终点经度
 * @param  decimal $latitude2  终点纬度
 * @param  int     $unit       单位 1:米 2:公里
 * @param  int     $decimal    精度 保留小数位数
 * @return decimal
 */
function getDistance($longitude1, $latitude1, $longitude2, $latitude2, $unit = 1, $decimal = 0)
{
    $EARTH_RADIUS = 6370.996; // 地球半径系数
    $PI           = 3.1415926;

    $radLat1 = $latitude1 * $PI / 180.0;
    $radLat2 = $latitude2 * $PI / 180.0;

    $radLng1 = $longitude1 * $PI / 180.0;
    $radLng2 = $longitude2 * $PI / 180.0;

    $a = $radLat1 - $radLat2;
    $b = $radLng1 - $radLng2;

    $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
    $distance = $distance * $EARTH_RADIUS * 1000;

    if ($unit == 2) {
        $distance = $distance / 1000;
    }

    return round($distance, $decimal);
}

/**
 * 获取具体位置
 * @author 贺强
 * @time   2019-01-16 09:22:55
 * @param  float  $lat 纬度
 * @param  float  $lng 经度
 * @return string      返回位置信息
 */
function getRealyAddress($lat, $lng)
{
    $address = '';
    if ($lat && $lng) {
        $arr     = changeToBaidu($lat, $lng);
        $url     = 'http://api.map.baidu.com/geocoder/v2/?callback=&location=' . $arr['y'] . ',' . $arr['x'] . '.&output=json&pois=1&ak=fKvpmBXsoCcx8AMGqOThmd2ZEXHpniVq';
        $content = file_get_contents($url);
        $place   = json_decode($content, true);
        $address = $place['result']['formatted_address'];
    }

    return $address;
}

/**
 * 转换为百度经纬度
 * @author 贺强
 * @time   2019-01-16 09:21:52
 * @param  float $lat 纬度
 * @param  float $lng 经度
 * @return float      百度经纬度
 */
function changeToBaidu($lat, $lng)
{
    $apiurl   = 'http://api.map.baidu.com/geoconv/v1/?coords=' . $lng . ',' . $lat . '&from=1&to=5&ak=fKvpmBXsoCcx8AMGqOThmd2ZEXHpniVq';
    $file     = file_get_contents($apiurl);
    $arrpoint = json_decode($file, true);
    return $arrpoint['result'][0];
}

/**
 * 获取地理位置信息
 * @author 贺强
 * @time   2019-01-05 10:35:18
 * @param  string $latlng 经纬度
 * @param  string $place  要获取的信息,all/location/formatted_address/city/province/district/direction/distance/street/street_number
 * @param  string $type   返回数据格式
 */
function getAddressInfo($latlng, $place = 'all', $type = 'json')
{
    $url  = "http://api.map.baidu.com/geocoder?location=30.990998,103.645966&output=json&key=28bcdd84fae25699606ffad27f8da77b";
    $url  = "http://api.map.baidu.com/geocoder?location={$latlng}&output={$type}&key=28bcdd84fae25699606ffad27f8da77b";
    $info = file_get_contents($url);
    if ($place === 'all') {
        return $info;
    } else {
        $info = json_decode($info, true);
        $info = $info['result'];
        if ($place === 'location' || $place === 'formatted_address') {
            if (empty($info[$place])) {
                return '';
            }
            return $info[$place];
        } else {
            $info = $info['addressComponent'];
            if (empty($info[$place])) {
                return '';
            }
            return $info[$place];
        }
    }
}

/**
 * 获取视频文件的缩略图
 * @author 贺强
 * @time   2019-01-16 09:47:14
 * @param  string  $file   视频文件路径
 * @param  integer $s      指定从什么时间开始截取，单位秒
 * @param  boolean $is_win 是否是 windows 系统
 * @return string          返回截取的图片保存路径
 */
function getVideoCover($file, $s = 11, $is_win = false)
{
    $ffmpeg = 'ffmpeg';
    $root   = ROOT_PATH . 'public';
    if ($is_win) {
        $ffmpeg = 'D:/ffmpeg/bin/ffmpeg.exe';
    }
    $path = '/uploads/cli/mp4/' . date('Y') . '/' . date('m') . '/' . date('d');
    if (!is_dir($root . $path)) {
        @mkdir($root . $path, 0755, true);
    }
    $path .= '/' . get_millisecond() . '.jpg';
    $strlen = strlen($file);
    $str    = "{$ffmpeg} -i {$file} -ss {$s} {$root}{$path}";
    system($str);
    return $path;
}
