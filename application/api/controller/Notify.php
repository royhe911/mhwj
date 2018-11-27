<?php
namespace app\api\controller;

use app\common\model\LogModel;
use app\common\model\RefundModel;
use app\common\model\UserOrderModel;

/**
 * Notify-控制器
 * @author 贺强
 * @time   2018-11-27 12:26:02
 */
class Notify extends \think\Controller
{

    /**
     * 预支付请求接口
     * @author 贺强
     * @time   2018-11-13 15:32:06
     */
    public function prepay()
    {
        $param = $this->request->post();
        if (empty($param['openid'])) {
            $msg = ['status' => 1, 'info' => 'openid 不能为空', 'data' => null];
        } elseif (empty($param['body'])) {
            $msg = ['status' => 2, 'info' => '商品描述不能为空', 'data' => null];
        } elseif (empty($param['out_trade_no'])) {
            $msg = ['status' => 3, 'info' => '订单号不能为空', 'data' => null];
        } elseif (empty($param['total_fee'])) {
            $msg = ['status' => 4, 'info' => '订单总金额不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        // 统一下单参数构造
        $nonce_str    = get_random_str(15);
        $unifiedorder = array(
            'appid'            => config('APPID_PLAYER'),
            'body'             => $param['body'],
            'mch_id'           => config('PAY_MCHID'),
            'nonce_str'        => $nonce_str,
            'notify_url'       => config('WEBSITE') . '/api/pay/notify',
            'openid'           => $param['openid'],
            'out_trade_no'     => $param['out_trade_no'],
            'spbill_create_ip' => get_client_ip(),
            'total_fee'        => $param['total_fee'],
            'trade_type'       => 'JSAPI',
        );
        $unifiedorder['sign'] = $this->make_sign($unifiedorder);
        // 数组转换为 xml
        $xmldata = array2xml($unifiedorder);
        $res     = $this->curl($url, $xmldata, false);
        if (!$res) {
            echo json_encode(['status' => 1, 'info' => '无法连接服务器', 'data' => null]);exit;
        }
        $res = xml2array($res);
        if (strval($res['return_code']) == 'FAIL') {
            echo json_encode(['status' => 3, 'info' => $res['return_msg'], 'data' => null]);exit;
        }
        if (!empty($res['result_code']) && strval($res['result_code']) == 'FAIL') {
            echo json_encode(['status' => 2, 'info' => $res['err_code_des'], 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '操作成功', 'data' => $res]);exit;
    }

    /**
     * 微信支付回调
     * @author 贺强
     * @time   2018-11-27 11:26:39
     */
    public function pay_notify()
    {
        $param = $this->request->get();
        if (empty($param)) {
            $param = $this->request->post();
        }
        file_put_contents('/www/wwwroot/wwwdragontangcom/application/api/controller/result.log', json_encode($param));
    }

    /**
     * 申请退款
     * @author 贺强
     * @time   2018-11-27 15:13:26
     */
    public function refund($param = null)
    {
        if (empty($param)) {
            $param = $this->request->post();
        }
        if (empty($param['type'])) {
            $msg = ['status' => 5, 'info' => '退款类型不能为空', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空', 'data' => null];
        } elseif (empty($param['out_trade_no'])) {
            $msg = ['status' => 3, 'info' => '订单号不能为空', 'data' => null];
        } elseif (empty($param['refund_fee'])) {
            $msg = ['status' => 2, 'info' => '退款金额不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $out_trade_no = $param['out_trade_no'];
        $type         = intval($param['type']);
        if ($type === 1) {
            $model = new UserOrderModel();
        } elseif ($type === 2) {
            $model = new PersonOrderModel();
        } else {
            echo json_encode(['status' => 9, 'info' => '非法操作', 'data' => null]);exit;
        }
        $order = $model->getModel(['order_num' => $out_trade_no], ['order_money', 'transaction_id']);
        if (!$order) {
            echo json_encode(['status' => 7, 'info' => '订单不存在', 'data' => null]);exit;
        }
        $nonce_str      = get_random_str(15);
        $out_refund_no  = get_millisecond(); // 退单号
        $total_fee      = $order['order_money'];
        $refund_fee     = $param['refund_fee']; // 退款金额
        $transaction_id = $order['transaction_id']; // 微信订单号
        $refund         = array(
            'appid'          => config('APPID_PLAYER'),
            'mch_id'         => config('PAY_MCHID'),
            'nonce_str'      => $nonce_str,
            'notify_url'     => config('WEBSITE') . '/api/pay/refund_url',
            'out_refund_no'  => $out_refund_no;
            'out_trade_no'   => $out_trade_no,
            'refund_fee'     => $refund_fee, // 退款金额
            'total_fee'      => $total_fee,
            'transaction_id' => $transaction_id, // 微信订单号
        );
        $refund['sign'] = $this->make_sign($refund);
        // 数组转换为 xml
        $xmldata = array2xml($refund);
        $url     = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $res     = $this->curl($url, $xmldata, false);
        if (!$res) {
            echo json_encode(['status' => 10, 'info' => '无法连接服务器', 'data' => null]);exit;
        }
        $res = xml2array($res);
        if (strval($res['return_code']) == 'FAIL') {
            echo json_encode(['status' => 20, 'info' => $res['return_msg'], 'data' => null]);exit;
        }
        $refund_desc = '';
        if (!empty($param['refund_desc'])) {
            $param['refund_desc'] = $refund_desc;
        }
        $data = ['type' => $type, 'uid' => $uid, 'nonce_str' => $nonce_str, 'transaction_id' => $transaction_id, 'out_trade_no' => $out_trade_no, 'out_refund_no' => $out_refund_no, 'total_fee' => $total_fee, 'refund_fee' => $refund_fee, 'refund_desc' => $refund_desc, 'addtime' => time()];
        $r    = new RefundModel();
        $res  = $r->add($data);
        if (!$res) {
            $l        = new LogModel();
            $log_data = ['type' => LogModel::TYPE_REFUND, 'content' => json_encode($data)];
            $l->addLog($data);
        }
        echo json_encode(['status' => 0, 'info' => '退款申请成功，退款金额会原路返回支付账号，请留意', 'data' => $res]);exit;
    }

    /**
     * 微信退款回调
     * @author 贺强
     * @time   2018-11-27 15:45:59
     */
    public function refund_notify()
    {
        $param = $this->request->get();
        if (empty($param)) {
            $param = $this->request->post();
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
        $stringA = '';
        foreach ($arr as $key => $val) {
            $stringA .= "{$key}={$val}&";
        }
        $stringA .= ('key=' . config('PRE_KEY'));
        $sign = strtoupper(md5($stringA));
        return $sign;
    }

}
