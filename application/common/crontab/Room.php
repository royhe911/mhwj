<?php
namespace app\common\crontab;

use app\common\model\MasterOrderModel;
use app\common\model\RoomMasterModel;
use app\common\model\RoomModel;
use app\common\model\RoomUserModel;
use app\common\model\UserOrderModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 销毁房间定时任务类
 * @author 贺强
 * @time   2018-11-27 19:21:03
 */
class Room extends Command
{
    /**
     * 设置定时任务时间执行规则
     * @author 贺强
     * @time   2018-11-27 19:21:07
     */
    protected function configure()
    {
        $this->setName('room')->setDescription('here is the remark');
    }

    /**
     * 执行定时任务
     * @author 贺强
     * @time   2018-11-27 19:21:15
     */
    protected function execute(Input $input, Output $output)
    {
        try {
            $ru   = new RoomUserModel();
            $list = $ru->getList(['status' => 5, 'ready_time' => ['lt', time() - 120]], ['room_id']);
            if ($list) {
                $r    = new RoomModel();
                $yule = $r->getList(['status' => 5, 'type' => 2], ['id']);
                $yid  = array_column($yule, 'id');
                $ids  = [];
                foreach ($list as $item) {
                    if (!in_array($item['room_id'], $yid)) {
                        $ids[] = $item['room_id'];
                    }
                }
                if ($ids) {
                    $ru->modifyField('status', 4, ['room_id' => ['in', $ids]]);
                    $r->modifyField('status', 9, ['id' => ['in', $ids]]);
                    $mo = new MasterOrderModel();
                    $mo->modifyField('status', 9, ['room_id' => ['in', $ids]]);
                    $rm = new RoomMasterModel();
                    $rm->delByWhere(['room_id' => ['in', $ids]]);
                    $uo = new UserOrderModel();
                    $uo->modifyField('status', 9, ['room_id' => ['in', $ids]]);
                    $uords = $uo->getList(['room_id' => ['in', $ids], 'transaction_id' => ['<>', '']], ['uid', 'order_num', 'order_money', 'transaction_id']);
                    foreach ($uords as $uord) {
                        $total_fee  = $uord['order_money'] * 100;
                        $refund_fee = $uord['order_money'] * 100;
                        // 退款测试1分
                        // $total_fee  = 1;
                        // $refund_fee = 1;
                        $this->exit_money($uord['order_num'], $total_fee, $refund_fee, $uord['transaction_id'], $uord['uid']);
                    }
                }
            }
        } catch (\Exception $e) {
            file_put_contents('/www/wwwroot/wwwdragontangcom/log/room' . time() . '.log', $e->getMessage());
        }
    }

    /**
     * 退款
     * @author 贺强
     * @time   2018-11-30 19:27:10
     * @param  string  $out_trade_no   本系统订单号
     * @param  integer $total_fee      订单总金额
     * @param  integer $refund_fee     退款金额
     * @param  string  $transaction_id 微信订单号
     */
    public function exit_money($out_trade_no = '', $total_fee = 0, $refund_fee = 0, $transaction_id = '', $uid = 0)
    {
        $appid         = 'wxe6f37de8e1e3225e';
        $mchid         = 1519826271;
        $nonce_str     = $this->get_random_str(15);
        $out_refund_no = $this->get_millisecond(); // 退单号
        $refund        = array(
            'appid'          => $appid,
            'mch_id'         => $mchid,
            'nonce_str'      => $nonce_str,
            'out_refund_no'  => $out_refund_no,
            'out_trade_no'   => $out_trade_no,
            'refund_fee'     => $refund_fee, // 退款金额
            'total_fee'      => $total_fee,
            'transaction_id' => $transaction_id, // 微信订单号
        );
        $refund['sign'] = $this->make_sign($refund);
        // 数组转换为 xml
        $xmldata     = $this->array2xml($refund);
        $url         = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $res         = $this->curl($url, $xmldata);
        $res         = $this->xml2array($res);
        $refund_desc = '房间销毁';
        $data        = ['type' => 1, 'uid' => $uid, 'nonce_str' => $nonce_str, 'transaction_id' => $transaction_id, 'out_trade_no' => $out_trade_no, 'out_refund_no' => $out_refund_no, 'total_fee' => $total_fee, 'refund_fee' => $refund_fee, 'refund_desc' => $refund_desc, 'addtime' => time()];
        $r           = new RefundModel();
        $res         = $r->add($data);
        if (!$res) {
            $l        = new LogModel();
            $log_data = ['type' => LogModel::TYPE_REFUND, 'content' => json_encode($data)];
            $l->addLog($data);
        }
    }

    /**
     * 生成签名
     * @author 贺强
     * @time   2018-11-13 10:17:56
     * @param  array  $arr 生成签名的数组
     * @return string      返回生成的签名
     */
    private function make_sign($arr)
    {
        $pre_key = '32292XXYJ629LRPWWLHM127XWNMDGDHL';
        $stringA = '';
        foreach ($arr as $key => $val) {
            $stringA .= "{$key}={$val}&";
        }
        $stringA .= ('key=' . $pre_key);
        $sign = strtoupper(md5($stringA));
        return $sign;
    }

    /**
     * 生成随机字符串
     * @param  integer $num 生成字符串的长度
     * @return string       返回生成的随机字符串
     */
    private function get_random_str($num = 8)
    {
        $pattern = 'AaZzBb0YyCc9XxDd8Ww7EeVvF6fUuG5gTtHhS4sIiRr3JjQqKkP2pLlO1oMmNn';
        $str     = '';
        for ($i = 0; $i < $num; $i++) {
            $str .= $pattern{mt_rand(0, 35)}; //生成 php 随机数
        }
        return $str;
    }

    /**
     * 获取毫秒数
     */
    private function get_millisecond()
    {
        list($microsecond, $time) = explode(' ', microtime()); //' '中间是一个空格
        return (float) sprintf('%.0f', (floatval($microsecond) + floatval($time)) * 1000);
    }

    /**
     * 数组转 xml
     * @author 贺强
     * @time   2018-11-13 15:03:03
     * @param  array  $arr 被转换的数组
     * @return string      返回转换后的 xml 字符串
     */
    private function array2xml($arr)
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
    private function xml2array($xml)
    {
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    /**
     * URL 请求
     * @author 贺强
     * @time   2018-10-30 12:13:06
     * @param  string  $url     请求地址
     * @param  string  $post    POST 数据
     * @param  string  $charset 编码方式，默认utf8
     * @return object           返回请求返回的数据
     */
    public function curl($url, $post = '', $charset = 'utf-8')
    {
        $keypath  = '/www/wwwroot/wwwdragontangcom/cert/apiclient_key.pem';
        $certpath = '/www/wwwroot/wwwdragontangcom/cert/apiclient_cert.pem';
        $ch       = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, false);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //设置证书
        //使用证书：cert 与 key 分别属于两个.pem文件
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, $certpath);
        //默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, $keypath);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            curl_close($ch);
            return false;
        }
    }

}
