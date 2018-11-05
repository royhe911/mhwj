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
