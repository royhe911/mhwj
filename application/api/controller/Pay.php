<?php
namespace app\api\controller;

use app\common\model\CouponModel;
use app\common\model\GameModel;
use app\common\model\MasterOrderModel;
use app\common\model\RoomModel;
use app\common\model\RoomUserModel;
use app\common\model\UserModel;
use app\common\model\UserOrderModel;

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

    /**
     * 预下单接口
     * @author 贺强
     * @time   2018-11-14 12:12:23
     * @param  CouponModel $c CouponModel 实例
     */
    public function preorder()
    {
        $param = $this->param;
        if (empty($param['room_id'])) {
            $msg = ['status' => 1, 'info' => '房间ID不能为空', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 2, 'info' => '玩家ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $u    = new UserModel();
        $r    = new RoomModel();
        $ru   = new RoomUserModel();
        $c    = new CouponModel();
        $data = [];
        $user = $u->getModel(['id' => $param['uid']], ['avatar', 'nickname']);
        if (!$user) {
            echo json_encode(['status' => 3, 'info' => '玩家不存在', 'data' => null]);exit;
        }
        $room = $r->getModel(['id' => $param['room_id']], ['uid', 'name', 'type']);
        if (!$room) {
            echo json_encode(['status' => 5, 'info' => '房间不存在', 'data' => null]);exit;
        }
        $rus = $ru->getModel(['room_id' => $param['room_id'], 'uid' => $param['uid']], ['price', 'num', 'total_money', 'status']);
        if (!$rus) {
            echo json_encode(['status' => 7, 'info' => '该用户未进入房间', 'data' => null]);exit;
        }
        if ($rus['total_money'] >= 35) {
            $cus = $c->getModel(['uid' => $param['uid'], 'status' => 0], ['type', 'money', 'over_time']);
            if ($cus) {
                if (!empty($cus['over_time'])) {
                    $cus['over_time'] = date('Y-m-d H:i:s', $cus['over_time']);
                }
                $data['last_money'] = $rus['total_money'] - $cus['money'];
                $data['coupon']     = $cus;
            }
        }
        if (empty($data['coupon'])) {
            $data['coupon']     = null;
            $data['last_money'] = $rus['total_money'];
        }
        $data['nickname']    = $user['nickname'];
        $data['avatar']      = $user['avatar'];
        $data['name']        = $room['name'];
        $data['type']        = $room['type'];
        $data['total_money'] = $rus['total_money'];
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $data]);exit;
    }

    /**
     * 添加房主订单
     * @author 贺强
     * @time   2018-11-13 19:12:25
     * @param  MasterOrderModel $mo MasterOrderModel 实例
     */
    public function add_master_order(MasterOrderModel $mo)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '陪玩师ID不能为空', 'data' => null];
        } elseif (empty($param['room_id'])) {
            $msg = ['status' => 2, 'info' => '房间ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $r    = new RoomModel();
        $room = $r->getModel(['id' => $param['room_id']], ['price', 'num', 'count']);
        if ($room) {
            $order_money          = $room['price'] * $room['num'] * $room['count'];
            $param['order_money'] = $order_money;
        }
        $param['order_num'] = get_millisecond();
        $param['addtime']   = time();
        $res                = $mo->add($param);
        if (!$res) {
            echo json_encode(['status' => 4, 'info' => '下单失败', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '下单成功', 'data' => null]);exit;
    }

    /**
     * 添加玩家订单
     * @author 贺强
     * @time   2018-11-13 19:13:18
     * @param  UserOrderModel $uo UserOrderModel 实例
     */
    public function add_user_order(UserOrderModel $uo)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '玩家ID不能为空', 'data' => null];
        } elseif (empty($param['room_id'])) {
            $msg = ['status' => 2, 'info' => '房间ID不能为空', 'data' => null];
        } elseif (empty($param['order_money'])) {
            $msg = ['status' => 3, 'info' => '订单金额不能为空', 'data' => null];
        }
        $count = $uo->getCount(['uid' => $param['uid'], 'room_id' => $param['room_id'], 'status' => ['<>', 3]]);
        if ($count) {
            echo json_encode(['status' => 5, 'info' => '不能重复下单', 'data' => null]);exit;
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $r    = new RoomModel();
        $room = $r->getModel(['id' => $param['room_id']]);
        if ($room) {
            $param['game_id']   = $room['game_id'];
            $param['play_type'] = $room['type'];
        }
        $mo     = new MasterOrderModel();
        $morder = $mo->getModel(['room_id' => $param['room_id']], 'id');
        if ($morder) {
            $param['morder_id'] = $morder['id'];
        }
        $param['order_num'] = get_millisecond();
        $param['addtime']   = time();
        $res                = $uo->add($param);
        if (!$res) {
            echo json_encode(['status' => 4, 'info' => '下单失败', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '下单成功', 'data' => null]);exit;
    }

    /**
     * 获取玩家订单
     * @author 贺强
     * @time   2018-11-14 17:08:03
     * @param  UserOrderModel $uo UserOrderModel 实例
     */
    public function get_user_order(UserOrderModel $uo)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '玩家ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $where = ['uid' => $param['uid'], 'status' => ['<>', 3]];
        if (!empty($param['status']) && $param['status'] != 'all') {
            $where['status'] = $param['status'];
        }
        // 分页参数
        $page     = 1;
        $pagesize = 10;
        $param    = $this->param;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $list = $uo->getList($where, ['order_num', 'game_id', 'play_type', 'order_money', 'addtime', 'status'], "$page,$pagesize");
        if ($list) {
            $u     = new UserModel();
            $user  = $u->getModel(['id' => $param['uid']]);
            $g     = new GameModel();
            $games = $g->getList(['is_delete' => 0], ['id', 'name']);
            $games = array_column($games, 'name', 'id');
            foreach ($list as &$item) {
                $item['nickname'] = $user['nickname'];
                $item['avatar']   = $user['avatar'];
                if (!empty($games[$item['game_id']])) {
                    $item['gamename'] = $games[$item['game_id']];
                } else {
                    $item['gamename'] = '';
                }
                if ($item['play_type'] === 1) {
                    $item['play_type'] = '实力上分';
                } else {
                    $item['play_type'] = '娱乐陪玩';
                }
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
                switch ($item['status']) {
                    case 1:
                        $item['status_txt'] = '未支付';
                        break;
                    case 6:
                        $item['status_txt'] = '已支付';
                        break;
                    case 10:
                        $item['status_txt'] = '已完成';
                        break;
                }
            }
            echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
        }
        echo json_encode(['status' => 4, 'info' => '暂无订单', 'data' => null]);exit;
    }

    /**
     * 修改玩家订单
     * @author 贺强
     * @time   2018-11-15 12:09:28
     * @param  UserOrderModel $uo UserOrderModel实例
     */
    public function modify_order(UserOrderModel $uo)
    {
        $param = $this->param;
        if (empty($param['order_num'])) {
            $msg = ['status' => 1, 'info' => '订单号不能为空', 'data' => null];
        } elseif (empty($param['status'])) {
            $msg = ['status' => 2, 'info' => '订单状态不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        if (intval($param['status']) === 6) {
            $param['pay_time'] = time();
        }
        $res = $uo->modifyField('status', $param['status'], ['order_num' => $param['order_num']]);
        if (!$res) {
            echo json_encode(['status' => 4, 'info' => '修改失败', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '修改成功', 'data' => null]);exit;
    }

    /**
     * 预支付请求接口
     * @author 贺强
     * @time   2018-11-13 15:32:06
     */
    public function prepay()
    {
        $param = $this->param;
        $url   = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
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
        // 统一下单参数构造
        $nonce_str    = get_random_num(15);
        $unifiedorder = array(
            'appid'            => config('APPID_PLAYER'),
            'body'             => $param['body'],
            'mch_id'           => config('PAY_MCHID'),
            'nonce_str'        => $nonce_str,
            'notify_url'       => 'https://' . config('WEBSITE') . '/api/pay/notify',
            'openid'           => $param['openid'],
            'out_trade_no'     => $param['out_trade_no'],
            'spbill_create_ip' => get_client_ip(),
            'total_fee'        => $param['total_fee'],
            'trade_type'       => 'JSAPI',
        );
        $unifiedorder['sign'] = $this->make_sign($unifiedorder);
        $xmldata              = array2xml($unifiedorder);
        $res                  = $this->curl($url, $xmldata, false);
        if (!$res) {
            echo json_encode(['status' => 1, 'info' => '无法连接服务器', 'data' => null]);exit;
        }
        $res = xml2array($res);
        if (strval($res['result_code']) == 'FAIL') {
            echo json_encode(['status' => 2, 'info' => $res['err_code_des'], 'data' => null]);exit;
        }
        if (strval($res['return_code']) == 'FAIL') {
            echo json_encode(['status' => 3, 'info' => $res['return_msg'], 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '操作成功', 'data' => $res]);exit;
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
