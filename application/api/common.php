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
    $ffmpeg = '/monchickey/ffmpeg/bin/ffmpeg';
    $root   = ROOT_PATH . 'public';
    if ($is_win) {
        $ffmpeg = 'D:/ffmpeg/bin/ffmpeg.exe';
    }
    $path = '/uploads/cli/video/' . date('Y') . '/' . date('m') . '/' . date('d');
    if (!is_dir($root . $path)) {
        @mkdir($root . $path, 0755, true);
    }
    $path .= '/' . get_millisecond() . '.jpg';
    $strlen = strlen($file);
    $str    = "{$ffmpeg} -i {$file} -ss {$s} {$root}{$path}";
    system($str);
    return $path;
}

/**
 * 得到转换编码后的拼音
 * @author 贺强
 * @time   2019-01-25 17:27:22
 * @param  string  $s       要得到拼音的文字
 * @param  boolean $isfirst 是否只获取拼音首字母
 * @return string           返回获取的拼音
 */
function utf8_to($s, $isfirst = false)
{
    return get_pinying(utf8_to_gb2312($s), $isfirst);
}

/**
 * UTF8转换为BG2312编码
 * @author 贺强
 * @time   2019-01-25 17:26:00
 * @param  string $s 要转换编码的文字
 * @return string    返回转换编码后的文字
 */
function utf8_to_gb2312($s)
{
    return iconv('UTF-8', 'GB2312//IGNORE', $s);
}

/**
 * 获取汉字拼音
 * @author 贺强
 * @time   2019-01-24 17:34:19
 * @param  string  $s       要获取拼音的汉字
 * @param  boolean $isfirst 是否只获取每个字的首字母
 * @return string           返回获取到的拼音
 */
function get_pinying($s, $isfirst = false)
{
    $res        = '';
    $len        = strlen($s);
    $pinyin_arr = get_pinyin_array();
    for ($i = 0; $i < $len; $i++) {
        $ascii = ord($s{$i});
        if ($ascii > 0x80) {
            $ascii2 = ord($s{ ++$i});
            $ascii  = $ascii * 256 + $ascii2 - 65536;
        }

        if ($ascii < 255 && $ascii > 0) {
            if (($ascii >= 48 && $ascii <= 57) || ($ascii >= 97 && $ascii <= 122)) {
                $res .= $s{$i}; // 0-9 a-z
            } elseif ($ascii >= 65 && $ascii <= 90) {
                $res .= strtolower($s{$i}); // A-Z
            } else {
                $res .= '_'; ////将符号转义 不替换符号$res .= $s{$i};
            }
        } elseif ($ascii < -20319 || $ascii > -10247) {
            $res .= '_';
        } else {
            foreach ($pinyin_arr as $py => $asc) {
                if ($asc <= $ascii) {
                    $res .= $isfirst ? $py{0} : $py;
                    break;
                }
            }
        }
    }
    return $res;
}

/**
 * 获取汉字的首字母
 * @author 贺强
 * @time   2019-01-24 17:37:19
 * @param  string $s 要获取首字母的汉字
 * @return string    返回获取到的首字母
 */
function get_first($s)
{
    $ascii = ord($s{0});
    if ($ascii > 0xE0) {
        $s = utf8_to_gb2312($s{0} . $s{1} . $s{2});
    } elseif ($ascii < 0x80) {
        if ($ascii >= 65 && $ascii <= 90) {
            return strtolower($s{0});
        } elseif ($ascii >= 97 && $ascii <= 122) {
            return $s{0};
        } else {
            return false;
        }
    }

    if (strlen($s) < 2) {
        return false;
    }

    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;

    if ($asc >= -20319 && $asc <= -20284) {
        return 'a';
    }

    if ($asc >= -20283 && $asc <= -19776) {
        return 'b';
    }

    if ($asc >= -19775 && $asc <= -19219) {
        return 'c';
    }

    if ($asc >= -19218 && $asc <= -18711) {
        return 'd';
    }

    if ($asc >= -18710 && $asc <= -18527) {
        return 'e';
    }

    if ($asc >= -18526 && $asc <= -18240) {
        return 'f';
    }

    if ($asc >= -18239 && $asc <= -17923) {
        return 'g';
    }

    if ($asc >= -17922 && $asc <= -17418) {
        return 'h';
    }

    if ($asc >= -17417 && $asc <= -16475) {
        return 'j';
    }

    if ($asc >= -16474 && $asc <= -16213) {
        return 'k';
    }

    if ($asc >= -16212 && $asc <= -15641) {
        return 'l';
    }

    if ($asc >= -15640 && $asc <= -15166) {
        return 'm';
    }

    if ($asc >= -15165 && $asc <= -14923) {
        return 'n';
    }

    if ($asc >= -14922 && $asc <= -14915) {
        return 'o';
    }

    if ($asc >= -14914 && $asc <= -14631) {
        return 'p';
    }

    if ($asc >= -14630 && $asc <= -14150) {
        return 'q';
    }

    if ($asc >= -14149 && $asc <= -14091) {
        return 'r';
    }

    if ($asc >= -14090 && $asc <= -13319) {
        return 's';
    }

    if ($asc >= -13318 && $asc <= -12839) {
        return 't';
    }

    if ($asc >= -12838 && $asc <= -12557) {
        return 'w';
    }

    if ($asc >= -12556 && $asc <= -11848) {
        return 'x';
    }

    if ($asc >= -11847 && $asc <= -11056) {
        return 'y';
    }

    if ($asc >= -11055 && $asc <= -10247) {
        return 'z';
    }

    return false;
}

/**
 * 得到拼音和ASCII码数组
 * @author 贺强
 * @time   2019-01-24 17:39:37
 * @return array 返回得到的数组
 */
function get_pinyin_array()
{
    $py_arr = config('PY');
    arsort($py_arr);
    return $py_arr;
    // static $py_arr;
    // if (isset($py_arr)) {
    //     return $py_arr;
    // }

    // $k      = 'a|ai|an|ang|ao|ba|bai|ban|bang|bao|bei|ben|beng|bi|bian|biao|bie|bin|bing|bo|bu|ca|cai|can|cang|cao|ce|ceng|cha|chai|chan|chang|chao|che|chen|cheng|chi|chong|chou|chu|chuai|chuan|chuang|chui|chun|chuo|ci|cong|cou|cu|cuan|cui|cun|cuo|da|dai|dan|dang|dao|de|deng|di|dian|diao|die|ding|diu|dong|dou|du|duan|dui|dun|duo|e|en|er|fa|fan|fang|fei|fen|feng|fo|fou|fu|ga|gai|gan|gang|gao|ge|gei|gen|geng|gong|gou|gu|gua|guai|guan|guang|gui|gun|guo|ha|hai|han|hang|hao|he|hei|hen|heng|hong|hou|hu|hua|huai|huan|huang|hui|hun|huo|ji|jia|jian|jiang|jiao|jie|jin|jing|jiong|jiu|ju|juan|jue|jun|ka|kai|kan|kang|kao|ke|ken|keng|kong|kou|ku|kua|kuai|kuan|kuang|kui|kun|kuo|la|lai|lan|lang|lao|le|lei|leng|li|lia|lian|liang|liao|lie|lin|ling|liu|long|lou|lu|lv|luan|lue|lun|luo|ma|mai|man|mang|mao|me|mei|men|meng|mi|mian|miao|mie|min|ming|miu|mo|mou|mu|na|nai|nan|nang|nao|ne|nei|nen|neng|ni|nian|niang|niao|nie|nin|ning|niu|nong|nu|nv|nuan|nue|nuo|o|ou|pa|pai|pan|pang|pao|pei|pen|peng|pi|pian|piao|pie|pin|ping|po|pu|qi|qia|qian|qiang|qiao|qie|qin|qing|qiong|qiu|qu|quan|que|qun|ran|rang|rao|re|ren|reng|ri|rong|rou|ru|ruan|rui|run|ruo|sa|sai|san|sang|sao|se|sen|seng|sha|shai|shan|shang|shao|she|shen|sheng|shi|shou|shu|shua|shuai|shuan|shuang|shui|shun|shuo|si|song|sou|su|suan|sui|sun|suo|ta|tai|tan|tang|tao|te|teng|ti|tian|tiao|tie|ting|tong|tou|tu|tuan|tui|tun|tuo|wa|wai|wan|wang|wei|wen|weng|wo|wu|xi|xia|xian|xiang|xiao|xie|xin|xing|xiong|xiu|xu|xuan|xue|xun|ya|yan|yang|yao|ye|yi|yin|ying|yo|yong|you|yu|yuan|yue|yun|za|zai|zan|zang|zao|ze|zei|zen|zeng|zha|zhai|zhan|zhang|zhao|zhe|zhen|zheng|zhi|zhong|zhou|zhu|zhua|zhuai|zhuan|zhuang|zhui|zhun|zhuo|zi|zong|zou|zu|zuan|zui|zun|zuo';
    // $v      = '-20319|-20317|-20304|-20295|-20292|-20283|-20265|-20257|-20242|-20230|-20051|-20036|-20032|-20026|-20002|-19990|-19986|-19982|-19976|-19805|-19784|-19775|-19774|-19763|-19756|-19751|-19746|-19741|-19739|-19728|-19725|-19715|-19540|-19531|-19525|-19515|-19500|-19484|-19479|-19467|-19289|-19288|-19281|-19275|-19270|-19263|-19261|-19249|-19243|-19242|-19238|-19235|-19227|-19224|-19218|-19212|-19038|-19023|-19018|-19006|-19003|-18996|-18977|-18961|-18952|-18783|-18774|-18773|-18763|-18756|-18741|-18735|-18731|-18722|-18710|-18697|-18696|-18526|-18518|-18501|-18490|-18478|-18463|-18448|-18447|-18446|-18239|-18237|-18231|-18220|-18211|-18201|-18184|-18183|-18181|-18012|-17997|-17988|-17970|-17964|-17961|-17950|-17947|-17931|-17928|-17922|-17759|-17752|-17733|-17730|-17721|-17703|-17701|-17697|-17692|-17683|-17676|-17496|-17487|-17482|-17468|-17454|-17433|-17427|-17417|-17202|-17185|-16983|-16970|-16942|-16915|-16733|-16708|-16706|-16689|-16664|-16657|-16647|-16474|-16470|-16465|-16459|-16452|-16448|-16433|-16429|-16427|-16423|-16419|-16412|-16407|-16403|-16401|-16393|-16220|-16216|-16212|-16205|-16202|-16187|-16180|-16171|-16169|-16158|-16155|-15959|-15958|-15944|-15933|-15920|-15915|-15903|-15889|-15878|-15707|-15701|-15681|-15667|-15661|-15659|-15652|-15640|-15631|-15625|-15454|-15448|-15436|-15435|-15419|-15416|-15408|-15394|-15385|-15377|-15375|-15369|-15363|-15362|-15183|-15180|-15165|-15158|-15153|-15150|-15149|-15144|-15143|-15141|-15140|-15139|-15128|-15121|-15119|-15117|-15110|-15109|-14941|-14937|-14933|-14930|-14929|-14928|-14926|-14922|-14921|-14914|-14908|-14902|-14894|-14889|-14882|-14873|-14871|-14857|-14678|-14674|-14670|-14668|-14663|-14654|-14645|-14630|-14594|-14429|-14407|-14399|-14384|-14379|-14368|-14355|-14353|-14345|-14170|-14159|-14151|-14149|-14145|-14140|-14137|-14135|-14125|-14123|-14122|-14112|-14109|-14099|-14097|-14094|-14092|-14090|-14087|-14083|-13917|-13914|-13910|-13907|-13906|-13905|-13896|-13894|-13878|-13870|-13859|-13847|-13831|-13658|-13611|-13601|-13406|-13404|-13400|-13398|-13395|-13391|-13387|-13383|-13367|-13359|-13356|-13343|-13340|-13329|-13326|-13318|-13147|-13138|-13120|-13107|-13096|-13095|-13091|-13076|-13068|-13063|-13060|-12888|-12875|-12871|-12860|-12858|-12852|-12849|-12838|-12831|-12829|-12812|-12802|-12607|-12597|-12594|-12585|-12556|-12359|-12346|-12320|-12300|-12120|-12099|-12089|-12074|-12067|-12058|-12039|-11867|-11861|-11847|-11831|-11798|-11781|-11604|-11589|-11536|-11358|-11340|-11339|-11324|-11303|-11097|-11077|-11067|-11055|-11052|-11045|-11041|-11038|-11024|-11020|-11019|-11018|-11014|-10838|-10832|-10815|-10800|-10790|-10780|-10764|-10587|-10544|-10533|-10519|-10331|-10329|-10328|-10322|-10315|-10309|-10307|-10296|-10281|-10274|-10270|-10262|-10260|-10256|-10254';
    // $key    = explode('|', $k);
    // $val    = explode('|', $v);
    // $py_arr = array_combine($key, $val);
    // arsort($py_arr);

    // return $py_arr;
}
