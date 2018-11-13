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
    $targetArr = [];
    $xmlObj    = simplexml_load_string($xml);
    $minArr    = (array) $xmlObj;
    foreach ($minArr as $key => $value) {
        if (is_string($value)) {
            $targetArr[$key] = $value;
        }
        if (is_object($value)) {
            $targetArr[$key] = xml2array($value->asXML());
        }
        if (is_array($value)) {
            foreach ($value as $zkey => $zvalue) {
                if (is_numeric($zkey)) {
                    $targetArr[$key][] = xml2array($zvalue->asXML());
                }
                if (is_string($zkey)) {
                    $targetArr[$key][$zkey] = xml2array($zvalue->asXML());
                }
            }
        }
    }
    return $targetArr;
}
