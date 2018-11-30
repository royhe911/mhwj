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
            $list = $ru->getList(['status' => 5, 'ready_time' => ['lt', time() - 300]], ['room_id']);
            if ($list) {
                $ids = '0';
                foreach ($list as $item) {
                    $ids .= ",{$item['room_id']}";
                }
                if ($ids !== '0') {
                    $ru->modifyField('status', 4, ['room_id' => ['in', $ids]]);
                    $r = new RoomModel();
                    $r->modifyField('status', 9, ['id' => ['in', $ids]]);
                    $mo = new MasterOrderModel();
                    $mo->modifyField('status', 9, ['room_id' => ['in', $ids]]);
                    $rm = new RoomMasterModel();
                    $rm->delByWhere(['room_id' => ['in', $ids]]);
                    $uo = new UserOrderModel();
                    $uo->modifyField('status', 9, ['room_id' => ['in', $ids]]);
                    $uords = $uo->getList(['room_id' => ['in', $ids]], ['uid', 'order_num', 'order_money']);
                    foreach ($uords as $uord) {
                        if (!empty($uord['transaction_id'])) {
                            $this->exit_money(1, 1, $uord['transaction_id']);
                        }
                    }
                }
            }
            echo "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";exit;
        } catch (\Exception $e) {
            file_put_contents('/www/wwwroot/wwwdragontangcom/log/' . time() . '.log', $e->getMessage());
        }
    }

    /**
     * 退款
     * @author 贺强
     * @time   2018-11-30 18:44:20
     * @param  int    $total_fee      订单金额
     * @param  int    $refund_fee     退款金额
     * @param  string $transaction_id 微信订单号
     */
    public function exit_money($total_fee, $refund_fee, $transaction_id)
    {
        $nonce_str     = get_random_str(15);
        $out_refund_no = get_millisecond(); // 退单号
        // $total_fee      = $order['order_money'];
        // $refund_fee     = $param['refund_fee']; // 退款金额
        // $transaction_id = $order['transaction_id']; // 微信订单号
        $refund = array(
            'appid'          => config('APPID_PLAYER'),
            'mch_id'         => config('PAY_MCHID'),
            'nonce_str'      => $nonce_str,
            'notify_url'     => config('WEBSITE') . '/api/pay/r_notify',
            'out_refund_no'  => $out_refund_no,
            'out_trade_no'   => $out_trade_no,
            'refund_fee'     => $refund_fee, // 退款金额
            'total_fee'      => $total_fee,
            'transaction_id' => $transaction_id, // 微信订单号
        );
        $refund['sign'] = $this->make_sign($refund);
        // 数组转换为 xml
        $xmldata     = array2xml($refund);
        $url         = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $res         = $this->curl($url, $xmldata, false);
        $res         = xml2array($res);
        $refund_desc = '房间销毁';
        $data        = ['type' => $type, 'uid' => $uid, 'nonce_str' => $nonce_str, 'transaction_id' => $transaction_id, 'out_trade_no' => $out_trade_no, 'out_refund_no' => $out_refund_no, 'total_fee' => $total_fee, 'refund_fee' => $refund_fee, 'refund_desc' => $refund_desc, 'addtime' => time()];
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
        $stringA = '';
        foreach ($arr as $key => $val) {
            $stringA .= "{$key}={$val}&";
        }
        $stringA .= ('key=' . config('PRE_KEY'));
        $sign = strtoupper(md5($stringA));
        return $sign;
    }

}
