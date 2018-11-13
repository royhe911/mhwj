<?php
namespace app\admin\controller;

/**
 * Pay-控制器
 * @author 贺强
 * @time   2018-11-12 11:35:22
 */
class Pay extends \think\Controller
{
    private $param = [];

    /**
     * 构造函数
     * @author 贺强
     * @time   2018-11-13 09:49:16
     */
    public function __construct()
    {
        $param = file_get_contents('php://input');
        $param = json_decode($param, true);
        if (empty($param['vericode'])) {
            echo json_encode(['status' => 300, 'info' => '非法参数', 'data' => null]);exit;
        }
        $vericode = $param['vericode'];
        unset($param['vericode']);
        $new_code = md5(config('MD5_PARAM'));
        if ($vericode !== $new_code) {
            echo json_encode(['status' => 100, 'info' => '非法参数', 'data' => null]);exit;
        }
        $this->param = $param;
    }

    public function prepay()
    {
        $param     = $this->param;
        $url       = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $openid    = $param['openid'];
        $body      = $param['body'];
        $order_sn  = $param['order_sn'];
        $total_fee = $param['total_fee'];
        // 统一下单参数构造
        $nonce_str    = get_random_num(15);
        $unifiedorder = array(
            'appid'            => config('APPID_PLAYER'),
            'body'             => $body,
            'mch_id'           => config('PAY_MCHID'),
            'nonce_str'        => $nonce_str,
            'notify_url'       => 'https://' . config('WEBSITE') . '/api/pay/notify',
            'openid'           => $openid,
            'out_trade_no'     => $order_sn,
            'spbill_create_ip' => get_client_ip(),
            'total_fee'        => $total_fee,
            'trade_type'       => 'JSAPI',
        );
    }

    private function make_sign($arr)
    {
        # code...
    }
}
