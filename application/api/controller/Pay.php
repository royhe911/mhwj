<?php
namespace app\api\controller;

use app\common\model\ConsumeModel;
use app\common\model\CouponModel;
use app\common\model\GameConfigModel;
use app\common\model\GameModel;
use app\common\model\MasterMoneyLogModel;
use app\common\model\MasterOrderModel;
use app\common\model\PersonMasterOrderModel;
use app\common\model\PersonOrderModel;
use app\common\model\PersonRoomModel;
use app\common\model\RoomModel;
use app\common\model\RoomUserModel;
use app\common\model\UserAttrModel;
use app\common\model\UserEvaluateModel;
use app\common\model\UserInviteModel;
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

    /**
     * 预下单接口
     * @author 贺强
     * @time   2018-11-14 12:12:23
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
        if ($rus['total_money'] >= config('LOWERMONEY')) {
            $cus = $c->getModel(['uid' => $param['uid'], 'status' => 0], ['id', 'type', 'money', 'over_time']);
            if ($cus) {
                if (!empty($cus['over_time'])) {
                    $cus['over_time'] = date('Y-m-d H:i:s', $cus['over_time']);
                }
                $data['last_money'] = $rus['total_money'] - $cus['money'];
                $data['coupon']     = $cus;
                $c->modifyField('status', 6, ['id' => $cus['id']]);
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
     */
    public function add_master_order()
    {
        $mo    = new MasterOrderModel();
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '陪玩师ID不能为空', 'data' => null];
        } elseif (empty($param['room_id'])) {
            $msg = ['status' => 2, 'info' => '房间ID不能为空', 'data' => null];
        } else {
            $morder = $mo->getCount(['uid' => $param['uid'], 'room_id' => $param['room_id']]);
            if ($morder) {
                $msg = ['status' => 3, 'info' => '不能重复下单', 'date' => null];
            }
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $r    = new RoomModel();
        $room = $r->getModel(['id' => $param['room_id']], ['price', 'num', 'count', 'type', 'game_id', 'region']);
        if ($room) {
            $order_money          = $room['price'] * $room['num'] * $room['count'];
            $param['order_money'] = $order_money;
            $param['play_type']   = $room['type'];
            $param['game_id']     = $room['game_id'];
            $param['region']      = $room['region'];
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
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $po = new PersonOrderModel();
        // 查询该玩家是否已下了订制订单
        $count = $po->getCount(['uid' => $param['uid'], 'status' => ['not in', '3,4,10']]);
        if ($count) {
            echo json_encode(['status' => 9, 'info' => '您已下了订制订单', 'date' => null]);exit;
        }
        $uorder = $uo->getModel(['uid' => $param['uid'], 'room_id' => $param['room_id'], 'status' => ['<>', 3], 'status' => ['<>', 10]], ['order_num']);
        if ($count) {
            echo json_encode(['status' => 0, 'info' => '重复下单成功', 'data' => ['order_num' => $uorder['order_num']]]);exit;
        }
        $r    = new RoomModel();
        $room = $r->getModel(['id' => $param['room_id']], ['game_id', 'type']);
        if ($room) {
            $param['game_id']   = $room['game_id'];
            $param['play_type'] = $room['type'];
        }
        $mo     = new MasterOrderModel();
        $morder = $mo->getModel(['room_id' => $param['room_id']], 'id');
        if ($morder) {
            $param['morder_id'] = $morder['id'];
        }
        $order_num          = get_millisecond();
        $param['order_num'] = $order_num;
        $param['addtime']   = time();
        // $param['status']    = 6;
        $res = $uo->add($param);
        if (!$res) {
            echo json_encode(['status' => 4, 'info' => '下单失败', 'data' => null]);exit;
        }
        $ui     = new UserInviteModel();
        $invite = $ui->getModel(['is_delete' => 0, 'invited_uid' => $param['uid']]);
        if ($invite) {
            $odata = ['uid' => $invite['uid'], 'type' => 1, 'money' => 5, 'over_time' => config('COUPONTERM') * 24 * 3666, 'addtime' => time()];
            $c     = new CouponModel();
            $c->add($odata);
        }
        echo json_encode(['status' => 0, 'info' => '下单成功', 'data' => ['order_num' => $order_num]]);exit;
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
        $list = $uo->getList($where, ['order_num', 'game_id', 'play_type', 'order_money', 'addtime', 'status'], "$page,$pagesize", 'addtime desc');
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
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
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
        // 订单类型，1房间订单  2订制订单
        $type = 1;
        if (!empty($param['type'])) {
            $type = intval($param['type']);
        }
        unset($param['type']);
        if ($type === 2) {
            $uo = new PersonOrderModel();
            if (intval($param['status']) === 10) {
                $uord = $uo->getModel(['order_num' => $param['order_num']]);
                if ($uord) {
                    $pmo   = new PersonMasterOrderModel();
                    $pmord = $pmo->getCount(['order_id' => $uord['id']]);
                    if (!$pmord) {
                        echo json_encode(['status' => 5, 'info' => '订单没有被接，不能完成', 'date' => null]);exit;
                    }
                }
            }
        }
        if (intval($param['status']) === 6) {
            $param['pay_time'] = time();
        }
        if ($type === 1) {
            if (intval($param['status']) === 6 || intval($param['status']) === 10) {
                $uorder = $uo->getModel(['order_num' => $param['order_num']], ['room_id']);
                if ($uorder) {
                    $ru = new RoomUserModel();
                    $ru->modifyField('status', $param['status'], ['room_id' => $uorder['room_id']]);
                }
            }
        }
        $res = $uo->modify($param, ['order_num' => $param['order_num']]);
        if (!$res) {
            echo json_encode(['status' => $res, 'info' => '修改失败', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '修改成功', 'data' => null]);exit;
    }

    /**
     * 订制下单
     * @author 贺强
     * @time   2018-11-15 15:23:27
     * @param  PersonOrderModel $po PersonOrderModel 实例
     */
    public function personal_preorder(PersonOrderModel $po)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '下单人不能为空', 'data' => null];
        } elseif (empty($param['game_id'])) {
            $msg = ['status' => 1, 'info' => '游戏ID不能为空', 'data' => null];
        } elseif (empty($param['region'])) {
            $msg = ['status' => 2, 'info' => '游戏大区不能为空', 'data' => null];
        } elseif (empty($param['para_id'])) {
            $msg = ['status' => 3, 'info' => '游戏段位不能为空', 'data' => null];
        } elseif (empty($param['price'])) {
            $msg = ['status' => 4, 'info' => '每局价格不能为空', 'data' => null];
        } elseif (empty($param['num'])) {
            $msg = ['status' => 5, 'info' => '游戏局数不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $ru = new RoomUserModel();
        // 查询譔下单人是否已在房间里
        $count = $ru->getCount(['uid' => $param['uid'], 'status' => ['not in', '3,4,10']]);
        if ($count) {
            echo json_encode(['status' => 9, 'info' => '您已在房间游戏中', 'date' => null]);exit;
        }
        $count = $po->getCount(['uid' => $param['uid'], 'status' => ['not in', '3,4,10']]);
        if ($count) {
            echo json_encode(['status' => 11, 'info' => '您有订单未完成', 'date' => null]);exit;
        }
        $order_num            = get_millisecond();
        $param['order_num']   = $order_num;
        $param['order_money'] = $param['price'] * $param['num'];
        $param['addtime']     = time();
        $res                  = $po->add($param);
        if (!$res) {
            echo json_encode(['status' => 44, 'info' => '下单失败', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '下单成功', 'data' => ['order_num' => $order_num]]);exit;
    }

    /**
     * 订制订单预支付
     * @author 贺强
     * @time   2018-11-15 16:46:23
     * @param  PersonOrderModel $po PersonOrderModel实例
     */
    public function person_ord_pay(PersonOrderModel $po)
    {
        $param = $this->param;
        if (empty($param['order_num'])) {
            echo json_encode(['status' => 1, 'info' => '订单号不能为空', 'data' => null]);exit;
        }
        $porder = $po->getModel(['order_num' => $param['order_num']], ['uid', 'order_num', 'game_id', 'region', 'para_id', 'num', 'price', 'type', 'addtime', 'order_money']);
        if (!$porder) {
            echo json_encode(['status' => 3, 'info' => '订单不存在', 'data' => null]);exit;
        }
        if (time() > $porder['addtime'] + 300) {
            echo json_encode(['status' => 5, 'info' => '订单已过期', 'data' => null]);exit;
        }
        if ($porder['type'] === 1) {
            $porder['type'] = '普通订单';
        }
        $g    = new GameModel();
        $game = $g->getModel(['id' => $porder['game_id']], ['name']);
        if ($game) {
            $porder['gamename'] = $game['name'];
        } else {
            $porder['gamename'] = '';
        }
        $gc       = new GameConfigModel();
        $gameconf = $gc->getModel(['game_id' => $porder['game_id'], 'para_id' => $porder['para_id']], ['para_str']);
        if ($gameconf) {
            $porder['para_str'] = $gameconf['para_str'];
        }
        $last_money = $porder['order_money'];
        if ($porder['order_money'] > config('LOWERMONEY')) {
            $c   = new CouponModel();
            $cus = $c->getModel(['uid' => $porder['uid'], 'status' => 0], ['id', 'money']);
            if ($cus) {
                $last_money -= $cus['money'];
                $c->modifyField('status', 6, ['id' => $cus['id']]);
            }
        }
        $porder['last_money'] = $last_money;
        $po->modifyField('order_money', $last_money, ['order_num' => $param['order_num']]);
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $porder]);exit;
    }

    /**
     * 玩家取消订单
     * @author 贺强
     * @time   2018-11-16 16:22:07
     * @param  PersonOrderModel $po PersonOrderModel 实例
     */
    public function cancel_order(PersonOrderModel $po)
    {
        $param = $this->param;
        if (empty($param['order_id'])) {
            $msg = ['status' => 1, 'info' => '订单ID不能为空', 'date' => null];
        }
        if (empty($param['uid'])) {
            $msg = ['status' => 2, 'info' => '下单人ID不能为空', 'date' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $count = $po->getCount(['id' => $param['order_id'], 'uid' => $param['uid']]);
        if (!$count) {
            echo json_encode(['status' => 4, 'info' => '非法操作', 'date' => null]);exit;
        }
        $pmo   = new PersonMasterOrderModel();
        $count = $pmo->getCount(['order_id' => $param['order_id']]);
        if ($count) {
            echo json_encode(['status' => 3, 'info' => '订单已被抢不能取消', 'date' => null]);exit;
        }
        $res = $po->modifyField('status', 3, ['id' => $param['order_id']]);
        if (!$res) {
            echo json_encode(['status' => 5, 'info' => '取消失败', 'date' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '取消成功', 'date' => null]);exit;
    }

    /**
     * 房间订单支付
     * @author 贺强
     * @time   2018-11-20 16:57:50
     * @param  UserOrderModel $uo UserOrderModel 实例
     */
    public function user_pay(UserOrderModel $uo)
    {
        $param = $this->param;
        if (empty($param['order_num'])) {
            $msg = ['status' => 1, 'info' => '订单号不能为空', 'date' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uorder = $uo->getModel(['order_num' => $param['order_num']]);
        if (!$uorder) {
            echo json_encode(['status' => 3, 'info' => '订单不存在', 'date' => null]);exit;
        }
        // 调用微信支付接口进行支付
        //
        //
        // 支付成功后更改订单
        $state = true;
        if (!$state) {
            echo json_encode(['status' => 4, 'info' => '支付失败', 'data' => null]);exit;
        }
        $res = $uo->pay_money($uorder);
        if ($res !== true) {
            echo json_encode(['status' => $res, 'info' => '支付失败', 'date' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '支付成功', 'data' => ['order_id' => $uorder['id']]]);exit;
    }

    /**
     * 订制下单支付
     * @author 贺强
     * @time   2018-11-15 16:20:59
     * @param  PersonOrderModel $po PersonOrderModel 实例
     */
    public function person_pay(PersonOrderModel $po)
    {
        $param = $this->param;
        if (empty($param['order_num'])) {
            echo json_encode(['status' => 1, 'info' => '订单号不能为空', 'data' => null]);exit;
        }
        $porder = $po->getModel(['order_num' => $param['order_num']]);
        if (!$porder) {
            echo json_encode(['status' => 2, 'info' => '订单不存在', 'data' => null]);exit;
        }
        $count = $po->getCount(['order_num' => $param['order_num'], 'addtime' => ['lt', time() - 300]]);
        if ($count) {
            echo json_encode(['status' => 3, 'info' => '订单已过期', 'date' => null]);exit;
        }
        // 调用微信支付接口进行支付
        //
        //
        // 支付成功后更改订单
        $state = true;
        if (!$state) {
            echo json_encode(['status' => 4, 'info' => '支付失败', 'data' => null]);exit;
        }
        $contribution = $porder['order_money'] * 100;
        $u            = new UserModel();
        if ($contribution > 0) {
            $u->increment('contribution', ['id' => $porder['uid']], $contribution);
        }
        $data = ['uid' => $porder['uid'], 'type' => 1, 'money' => $porder['order_money'], 'addtime' => time()];
        $c    = new ConsumeModel();
        $c->add($data);
        $res = $po->modifyField('status', 6, ['order_num' => $param['order_num']]);
        echo json_encode(['status' => 0, 'info' => '支付成功', 'data' => ['order_id' => $porder['id']]]);exit;
    }

    /**
     * 获取任务订单
     * @author 贺强
     * @time   2018-11-15 19:06:20
     * @param  PersonOrderModel $po PersonOrderModel 实例
     */
    public function person_task(PersonOrderModel $po)
    {
        $param = $this->param;
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
        $list   = $po->getList(['status' => 6], ['id', 'uid', 'order_num', 'game_id', 'region', 'para_id', 'price', 'num', 'type', 'order_money'], "$page,$pagesize");
        $g      = new GameModel();
        $games  = $g->getList(['is_delete' => 0], ['id', 'name']);
        $games  = array_column($games, 'name', 'id');
        $gc     = new GameConfigModel();
        $gconfs = $gc->getList(null, ['game_id', 'para_id', 'para_str']);
        $gc_arr = [];
        foreach ($gconfs as $gco) {
            $gc_arr[$gco['game_id']][$gco['para_id']] = $gco['para_str'];
        }
        foreach ($list as &$item) {
            if (!empty($games[$item['game_id']])) {
                $item['gamename'] = $games[$item['game_id']];
            } else {
                $item['gamename'] = '';
            }
            if (!empty($gc_arr[$item['game_id']]) && !empty($gc_arr[$item['game_id']][$item['para_id']])) {
                $item['para_str'] = $gc_arr[$item['game_id']][$item['para_id']];
            } else {
                $item['para_str'] = '';
            }
            if ($item['type'] === 1) {
                $item['type'] = '普通订单';
            }
            if ($item['region'] === 1) {
                $item['region'] = 'QQ';
            } else {
                $item['region'] = '微信';
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 陪玩师抢单
     * @author 贺强
     * @time   2018-11-15 20:53:39
     * @param  PersonMasterOrderModel $pmo PersonMasterOrderModel 实例
     */
    public function robbing(PersonMasterOrderModel $pmo)
    {
        $param = $this->param;
        if (empty($param['master_id'])) {
            $msg = ['status' => 1, 'info' => '陪玩师ID不能为空', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 3, 'info' => '玩家ID不能为空', 'data' => null];
        } elseif (empty($param['order_id'])) {
            $msg = ['status' => 5, 'info' => '订单ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $u     = new UserModel();
        $count = $u->getCount(['id' => $param['master_id'], 'type' => 2, 'status' => 8]);
        if (!$count) {
            echo json_encode(['status' => 6, 'info' => '您还未认证成为陪玩师，请先认证', 'date' => null]);exit;
        }
        $po     = new PersonOrderModel();
        $porder = $po->getModel(['id' => $param['order_id']]);
        // 查询订制订单
        $ord_where = ['uid' => $param['master_id'], 'game_id' => $porder['game_id'], 'status' => 8];
        if ($porder['play_type'] === 1) {
            $ord_where['play_type'] = 1;
        }
        $ua    = new UserAttrModel();
        $count = $ua->getCount($ord_where);
        if (!$count) {
            echo json_encode(['status' => 7, 'info' => '您还未认证该游戏的陪玩类型', 'date' => null]);exit;
        }
        $pmcount = $pmo->getCount(['order_id' => $param['order_id']]);
        if ($pmcount) {
            echo json_encode(['status' => 1, 'info' => '订单已被抢', 'data' => null]);exit;
        }
        $param['addtime'] = time();
        $res              = $pmo->robbing_order($param);
        $msg              = ['status' => $res, 'data' => null];
        if ($res === 1) {
            $msg['info'] = '订单已被抢';
        } elseif ($res === 2) {
            $msg['info'] = '订单已取消';
        }
        if ($msg['status'] === true) {
            $pr    = new PersonRoomModel();
            $count = $pr->getCount(['order_id' => $param['order_id']]);
            if (!$count) {
                $pr->add($param);
            } else {
                $pr->modify($param, ['order_id' => $param['order_id']]);
            }
            $msg = ['status' => 0, 'info' => '抢单成功', 'data' => ['order_id' => $param['order_id']]];
        }
        echo json_encode($msg);exit;
    }

    /**
     * 获取陪玩师房间订单
     * @author 贺强
     * @time   2018-11-18 16:48:21
     * @param  MasterOrderModel $mo MasterOrderModel 实例
     */
    public function get_master_order(MasterOrderModel $mo)
    {
        $param = $this->param;
        if (empty($param['master_id'])) {
            $msg = ['status' => 1, 'info' => '陪玩师ID不能为空', 'date' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $where = ['uid' => $param['master_id']];
        if (!empty($param['status'])) {
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
        $list = $mo->getList($where, ['order_num', 'uid', 'game_id', 'play_type', 'order_money', 'addtime', 'status'], "$page,$pagesize", 'addtime desc');
        if ($list) {
            $uids  = array_column($list, 'uid');
            $u     = new UserModel();
            $users = $u->getList(['type' => 2, 'id' => ['in', $uids]], ['id', 'nickname', 'avatar']);
            $users = array_column($users, null, 'id');
            $g     = new GameModel();
            $games = $g->getList(['is_delete' => 0], ['id', 'name']);
            $games = array_column($games, 'name', 'id');
            foreach ($list as &$item) {
                if (!empty($users[$item['uid']])) {
                    $user = $users[$item['uid']];
                    // 属性赋值
                    $item['nickname'] = $user['nickname'];
                    $item['avatar']   = $user['avatar'];
                } else {
                    $item['nickname'] = '';
                    $item['avatar']   = '';
                }
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
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'date' => $list]);exit;
    }

    /**
     * 获取陪玩师订制订单
     * @author 贺强
     * @time   2018-11-18 17:12:12
     * @param  PersonMasterOrderModel $pmo PersonMasterOrderModel 实例
     * @param  PersonOrderModel       $po  PersonOrderModel 实例
     */
    public function get_master_pord(PersonMasterOrderModel $pmo)
    {
        $param = $this->param;
        if (empty($param['master_id'])) {
            $msg = ['status' => 1, 'info' => '陪玩师ID不能为空', 'date' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $where = ['a.master_id' => $param['master_id']];
        if (!empty($param['status'])) {
            $where['po.status'] = $param['status'];
        }
        // 分页参数
        $page     = 1;
        $pagesize = 10;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $list = $pmo->getJoinList([['m_person_order po', ['a.order_id=po.id']]], $where, ['master_id', 'order_num', 'a.uid', 'game_id', 'play_type', 'order_money', 'a.addtime', 'po.status'], "$page,$pagesize");
        if ($list) {
            $uids   = array_column($list, 'uid');
            $u      = new UserModel();
            $master = $u->getModel(['id' => $param['master_id']]);
            $users  = $u->getList(['type' => 1, 'id' => ['in', $uids]], ['id', 'nickname']);
            $users  = array_column($users, 'nickname', 'id');
            $g      = new GameModel();
            $games  = $g->getList(['is_delete' => 0], ['id', 'name']);
            $games  = array_column($games, 'name', 'id');
            foreach ($list as &$item) {
                $item['master_nickname'] = $master['nickname'];
                $item['master_avatar']   = $master['avatar'];
                if (!empty($users[$item['uid']])) {
                    $item['nickname'] = $users[$item['uid']];
                } else {
                    $item['nickname'] = '';
                }
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
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'date' => $list]);exit;
    }

    /**
     * 获取订制订单列表
     * @author 贺强
     * @time   2018-11-18 09:53:02
     * @param  PersonOrderModel $po PersonOrderModel 实例
     */
    public function get_person_order(PersonOrderModel $po)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '玩家ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $where = ['status' => ['<>', 4], 'uid' => $param['uid']];
        if (!empty($param['status'])) {
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
        $list = $po->getList($where, ['order_num', 'uid', 'game_id', 'play_type', 'order_money', 'addtime', 'status'], "$page,$pagesize", 'addtime desc');
        if ($list) {
            $uids  = array_column($list, 'uid');
            $u     = new UserModel();
            $users = $u->getList(['type' => 1, 'id' => ['in', $uids]], ['id', 'nickname']);
            $users = array_column($users, 'nickname', 'id');
            $g     = new GameModel();
            $games = $g->getList(['is_delete' => 0], ['id', 'name']);
            $games = array_column($games, 'name', 'id');
            foreach ($list as &$item) {
                if (!empty($users[$item['uid']])) {
                    $item['nickname'] = $users[$item['uid']];
                } else {
                    $item['nickname'] = '';
                }
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
            }
        }
        $msg = ['status' => 0, 'info' => '获取成功', 'date' => $list];
        echo json_encode($msg);exit;
    }

    /**
     * 订制订单详情
     * @author 贺强
     * @time   2018-11-19 10:34:50
     * @param  PersonOrderModel $po PersonOrderModel 实例
     */
    public function get_pord_info(PersonOrderModel $po)
    {
        $param = $this->param;
        if (empty($param['order_num'])) {
            $msg = ['status' => 1, 'info' => '订单号不能为空', 'date' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $porder = $po->getModel(['order_num' => $param['order_num']]);
        if (!$porder) {
            echo json_encode(['status' => 3, 'info' => '订单不存在', 'date' => null]);exit;
        }
        if (!empty($porder['addtime'])) {
            $porder['addtime'] = date('Y-m-d H:i:s', $porder['addtime']);
        }
        $u       = new UserModel();
        $pmo     = new PersonMasterOrderModel();
        $pmorder = $pmo->getModel(['order_id' => $porder['id']]);
        // 属性赋值
        $porder['master_avatar']   = '';
        $porder['master_nickname'] = '';
        $porder['master_id']       = 0;
        if ($pmorder) {
            $user = $u->getModel(['id' => $pmorder['master_id']], ['avatar', 'nickname']);
            if ($user) {
                $porder['master_id']       = $pmorder['master_id'];
                $porder['master_avatar']   = $user['avatar'];
                $porder['master_nickname'] = $user['nickname'];
            }
        }
        $g    = new GameModel();
        $game = $g->getModel(['id' => $porder['game_id']], ['name']);
        if ($game) {
            $porder['gamename'] = $game['name'];
        }
        if ($porder['region'] === 1) {
            $porder['region'] = 'QQ';
        } else {
            $porder['region'] = '微信';
        }
        if ($porder['play_type'] === 1) {
            $porder['play_type'] = '实力上分';
        } else {
            $porder['play_type'] = '娱乐陪玩';
        }
        $ue      = new UserEvaluateModel();
        $comment = $ue->getModel(['type' => 2, 'order_id' => $porder['id']], ['uid', 'content', 'score']);
        if ($comment) {
            $user = $u->getModel(['id' => $comment['uid']], ['avatar', 'nickname']);
            // 属性赋值
            $comment['user_nickname'] = $user['nickname'];
            $comment['user_avatar']   = $user['avatar'];
        }
        $porder['comment'] = $comment;
        echo json_encode(['status' => 0, 'info' => '获取成功', 'date' => $porder]);exit;
    }

    /**
     * 获取玩家房间订单详情
     * @author 贺强
     * @time   2018-11-19 10:46:02
     * @param  UserOrderModel $u UserOrderModel 实例
     */
    public function get_uord_info(UserOrderModel $uo)
    {
        $param = $this->param;
        if (empty($param['order_num'])) {
            $msg = ['status' => 1, 'info' => '订单号不能为空', 'date' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uorder = $uo->getModel(['order_num' => $param['order_num']]);
        if (!$uorder) {
            echo json_encode(['status' => 3, 'info' => '订单不存在', 'date' => null]);exit;
        }
        if (!empty($uorder['addtime'])) {
            $uorder['addtime'] = date('Y-m-d H:i:s', $uorder['addtime']);
        }
        if (!empty($uorder['pay_time'])) {
            $uorder['pay_time'] = date('Y-m-d H:i:s', $uorder['pay_time']);
        }
        $g    = new GameModel();
        $game = $g->getModel(['id' => $uorder['game_id']], ['name']);
        if ($game) {
            $uorder['gamename'] = $game['name'];
        } else {
            $uorder['gamename'] = '';
        }
        $uid  = 0;
        $r    = new RoomModel();
        $room = $r->getModel(['id' => $uorder['room_id']], ['uid']);
        if ($room) {
            $uid = $room['uid'];
        }
        $u    = new UserModel();
        $user = $u->getModel(['id' => $uid], ['avatar', 'nickname']);
        if ($user) {
            $uorder['master_avatar']   = $user['avatar'];
            $uorder['master_nickname'] = $user['nickname'];
        } else {
            $uorder['master_avatar']   = '';
            $uorder['master_nickname'] = '';
        }
        if ($uorder['region'] === 1) {
            $uorder['region'] = 'QQ';
        } else {
            $uorder['region'] = '微信';
        }
        if ($uorder['play_type'] === 1) {
            $uorder['play_type'] = '实力上分';
        } else {
            $uorder['play_type'] = '娱乐陪玩';
        }
        $ue      = new UserEvaluateModel();
        $comment = $ue->getModel(['type' => 1, 'order_id' => $uorder['id']], ['uid', 'content', 'score']);
        if ($comment) {
            $user = $u->getModel(['id' => $comment['uid']], ['avatar', 'nickname']);
            // 属性赋值
            $comment['user_nickname'] = $user['nickname'];
            $comment['user_avatar']   = $user['avatar'];
        }
        $uorder['comment'] = $comment;
        echo json_encode(['status' => 0, 'info' => '获取成功', 'date' => $uorder]);exit;
    }

    /**
     * 获取陪玩师房间订单详情
     * @author 贺强
     * @time   2018-11-19 10:58:26
     * @param  MasterOrderModel $mo MasterOrderModel 实例
     */
    public function get_mord_info(MasterOrderModel $mo)
    {
        $param = $this->param;
        if (empty($param['order_num'])) {
            $msg = ['status' => 1, 'info' => '订单号不能为空', 'date' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $morder = $mo->getModel(['order_num' => $param['order_num']]);
        if (!$morder) {
            echo json_encode(['status' => 3, 'info' => '订单不存在', 'date' => null]);exit;
        }
        $r    = new RoomModel();
        $room = $r->getModel(['id' => $morder['room_id']]);
        if ($room['uid'] === $morder['uid']) {
            $morder['can_complete'] = 1;
        } else {
            $morder['can_complete'] = 0;
        }
        if (!empty($morder['addtime'])) {
            $morder['addtime'] = date('Y-m-d H:i:s', $morder['addtime']);
        }
        if (!empty($morder['complete_time'])) {
            $morder['complete_time'] = date('Y-m-d H:i:s', $morder['complete_time']);
        }
        $u    = new UserModel();
        $user = $u->getModel(['id' => $morder['uid']], ['avatar', 'nickname']);
        if ($user) {
            $morder['master_avatar']   = $user['avatar'];
            $morder['master_nickname'] = $user['nickname'];
        } else {
            $morder['master_avatar']   = '';
            $morder['master_nickname'] = '';
        }
        $g    = new GameModel();
        $game = $g->getModel(['id' => $morder['game_id']], ['name']);
        if ($game) {
            $morder['gamename'] = $game['name'];
        } else {
            $morder['gamename'] = '';
        }
        if ($morder['region'] === 1) {
            $morder['region'] = 'QQ';
        } else {
            $morder['region'] = '微信';
        }
        if ($morder['play_type'] === 1) {
            $morder['play_type'] = '实力上分';
        } else {
            $morder['play_type'] = '娱乐陪玩';
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'date' => $morder]);exit;
    }

    /**
     * 获取玩家优惠卷
     * @author 贺强
     * @time   2018-11-18 11:43:09
     * @param  CouponModel $c CouponModel 实例
     */
    public function get_user_coupon(CouponModel $c)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空', 'date' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $list = $c->getList(['uid' => $param['uid']], ['money', 'over_time', 'type']);
        if ($list) {
            foreach ($list as &$item) {
                if (!empty($item['over_time'])) {
                    $item['over_time'] = date('Y-m-d H:i:s', $item['over_time']);
                }
                if ($item['type'] === 1) {
                    $item['desc'] = '满' . config('LOWERMONEY') . '减' . $item['money'];
                } else {
                    $item['desc'] = '';
                }
            }
        }
        $msg = ['status' => 0, 'info' => '获取成功', 'date' => $list];
        echo json_encode($msg);exit;
    }

    /**
     * 获取玩家消费记录
     * @author 贺强
     * @time   2018-11-18 12:04:08
     * @param  ConsumeModel $c ConsumeModel 实例
     */
    public function get_consume_log(ConsumeModel $c)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空', 'date' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $where = ['uid' => $param['uid']];
        if (!empty($param['addtime'])) {
            $begin = date('Y-m-01', strtotime($param['addtime']));
        } else {
            $begin = date('Y-m-01');
        }
        // 取每月第一天和最后一天的数据
        $where['addtime'] = ['between', [strtotime($begin), strtotime(date('Y-m-d', strtotime("$begin +1 month -1 d")))]];
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
        $list = $c->getList($where, ['uid', 'money', 'addtime'], "$page,$pagesize");
        if ($list) {
            $u    = new UserModel();
            $user = $u->getModel(['id' => $param['uid']], ['nickname', 'avatar']);
            foreach ($list as &$item) {
                $item['nickname'] = $user['nickname'];
                $item['avatar']   = $user['avatar'];
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'date' => $list]);exit;
    }

    /**
     * 陪玩师提现记录
     * @author 贺强
     * @time   2018-11-18 15:47:48
     * @param  MasterMoneyLogModel $mml MasterMoneyLogModel 实例
     */
    public function get_money_log(MasterMoneyLogModel $mml)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '陪玩师ID不能为空', 'date' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $where = ['uid' => $param['uid']];
        if (!empty($param['addtime'])) {
            $begin = date('Y-m-01', strtotime($param['addtime']));
        } else {
            $begin = date('Y-m-01');
        }
        // 取每月第一天和最后一天的数据
        $where['addtime'] = ['between', [strtotime($begin), strtotime(date('Y-m-d', strtotime("$begin +1 month -1 d")))]];
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
        $list = $mml->getList($where, ['uid', 'money', 'addtime'], "$page,$pagesize");
        $user = [];
        if ($list) {
            $u    = new UserModel();
            $user = $u->getModel(['id' => $param['uid']], ['nickname', 'avatar', 'money']);
            foreach ($list as &$item) {
                $item['nickname'] = $user['nickname'];
                $item['avatar']   = $user['avatar'];
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
            }
        }
        if (empty($user['money'])) {
            $user['money'] = 0;
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'date' => ['money' => $user['money'], 'log' => $list]]);exit;
    }

}
