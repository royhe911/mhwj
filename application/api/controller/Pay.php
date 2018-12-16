<?php
namespace app\api\controller;

use app\common\model\ConsumeModel;
use app\common\model\CouponModel;
use app\common\model\GameConfigModel;
use app\common\model\GameModel;
use app\common\model\LogModel;
use app\common\model\MasterMoneyLogModel;
use app\common\model\MasterOrderModel;
use app\common\model\PersonMasterOrderModel;
use app\common\model\PersonOrderModel;
use app\common\model\PersonRoomModel;
use app\common\model\RoomMasterModel;
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
                $msg = ['status' => 3, 'info' => '不能重复下单', 'data' => null];
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
        $uid = $param['uid'];
        $po  = new PersonOrderModel();
        // 查询该玩家是否已下了订制订单
        $count = $po->getCount(['uid' => $uid, 'status' => ['in', '1,6,7']]);
        if ($count) {
            echo json_encode(['status' => 9, 'info' => '您已下了订制订单', 'data' => null]);exit;
        }
        $uorder = $uo->getModel(['uid' => $uid, 'room_id' => $param['room_id'], 'status' => ['<>', 3], 'status' => ['<>', 10]], ['order_num']);
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
        $invite = $ui->getModel(['is_delete' => 0, 'invited_uid' => $uid]);
        if ($invite) {
            $odata = ['uid' => $invite['uid'], 'type' => 1, 'money' => 5, 'over_time' => config('COUPONTERM') * 24 * 3666, 'addtime' => time()];
            $c     = new CouponModel();
            $c->add($odata);
        }
        $last_money = floatval($param['order_money']);
        if ($last_money > config('LOWERMONEY')) {
            $c   = new CouponModel();
            $cus = $c->getModel(['uid' => $uid, 'status' => 0], ['id', 'money']);
            if ($cus) {
                $last_money -= $cus['money'];
                $c->modifyField('status', 6, ['id' => $cus['id']]);
            }
        }
        $total_fee = $last_money * 100;
        $total_fee = 1;
        // 调用微信预支付
        $pay_data = $this->wxpay($uid, $order_num, $total_fee);
        if ($pay_data === false) {
            $msg = ['status' => 5, 'info' => '玩家不存在', 'data' => null];
        } elseif ($pay_data === 1) {
            $msg = ['status' => 6, 'info' => '连接服务器失败', 'data' => null];
        } elseif ($pay_data === 2) {
            $msg = ['status' => 7, 'info' => '预支付失败', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $u = new UserModel();
        if ($total_fee > 0) {
            $u->increment('contribution', ['id' => $uid], $total_fee);
        }
        $data = ['uid' => $uid, 'type' => 1, 'money' => $last_money, 'addtime' => time()];
        $csm  = new ConsumeModel();
        $res  = $csm->add($data);
        echo json_encode(['status' => 0, 'info' => '下单成功', 'data' => $pay_data]);exit;
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
        $where = ['uid' => $param['uid'], 'status' => ['in', '1,6,10']];
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
        $list = $uo->getList($where, ['order_num', 'game_id', 'play_type', 'order_money', 'addtime', 'status'], "$page,$pagesize", 'status,addtime desc');
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
        $status = intval($param['status']);
        if ($type === 2) {
            $uo = new PersonOrderModel();
        }
        $order_num = $param['order_num'];
        $uorder    = $uo->getModel(['order_num' => $order_num]);
        if ($uorder) {
            if ($status === 10 && $type === 2) {
                $pmo   = new PersonMasterOrderModel();
                $pmord = $pmo->getModel(['order_id' => $uorder['id']]);
                if (!$pmord) {
                    echo json_encode(['status' => 5, 'info' => '订单没有被接，不能完成', 'data' => null]);exit;
                }
                $master_id = $pmord['master_id'];
            }
            if (($status === 6 || $status === 10) && $type === 1) {
                $r    = new RoomModel();
                $room = $r->getModel(['id' => $uorder['room_id']]);
                if ($status === 10 && $room['status'] !== 8 && $room['status'] !== 10) {
                    echo json_encode(['status' => 11, 'info' => '陪玩师还没有开车，不能完成', 'data' => null]);exit;
                }
                $ru = new RoomUserModel();
                $ru->modifyField('status', $status, ['room_id' => $uorder['room_id']]);
                $master_id = $room['uid'];
            }
        }
        if ($status === 6) {
            $param['pay_time'] = time();
        }
        $res = $uo->modify($param, ['order_num' => $param['order_num']]);
        if (!$res) {
            echo json_encode(['status' => $res, 'info' => '修改失败', 'data' => null]);exit;
        }
        if ($status === 10) {
            $money = $uorder['order_money'] * config('RATIO');
            $uids  = [$master_id];
            if ($type === 1) {
                $rm  = new RoomMasterModel();
                $mss = $rm->getList(['room_id' => $uorder['room_id']], ['uid']);
                if (!empty($mss)) {
                    $uids = array_merge($uids, array_column($mss, 'uid'));
                }
            }
            if ($type === 2) {
                $pr = new PersonRoomModel();
                $pr->modifyField('is_delete', 1, ['order_id' => $uorder['id']]);
            }
            $money = round($money / count($uids), 2);
            $u     = new UserModel();
            foreach ($uids as $uid) {
                $u->increment('money', ['id' => $uid], $money);
                $u->increment('acc_money', ['id' => $uid], $money);
            }
        }
        echo json_encode(['status' => 0, 'info' => '修改成功', 'data' => null]);exit;
    }

    /**
     * 陪玩师修改订制订单状态
     * @author 贺强
     * @time   2018-12-06 16:47:18
     * @param  PersonMasterOrderModel $pmo PersonMasterOrderModel 实例
     */
    public function master_porder(PersonMasterOrderModel $pmo)
    {
        $param = $this->param;
        if (empty($param['order_num'])) {
            $msg = ['status' => 1, 'info' => '订单号不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $status = 10;
        if (!empty($param['status'])) {
            $status = $param['status'];
        }
        $po     = new PersonOrderModel();
        $porder = $po->getModel(['order_num' => $param['order_num']]);
        if (!$porder) {
            echo json_encode(['status' => 5, 'info' => '订单不存在', 'data' => null]);exit;
        }
        // 如果陪玩师点开车则修改订单状态
        if ($status === 8) {
            $po->modifyField('status', 8, ['order_num' => $param['order_num']]);
        }
        if ($status === 10 && $porder['status'] !== 8 && $porder['status'] !== 10) {
            echo json_encode(['status' => 7, 'info' => '该订单不能被完成', 'data' => null]);exit;
        }
        $res = $pmo->modifyField('status', $status, ['order_id' => $porder['id']]);
        if ($res === false) {
            echo json_encode(['status' => 9, 'info' => '修改失败', 'data' => null]);exit;
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
        $count = $ru->getCount(['uid' => $param['uid'], 'status' => ['not in', '4,10']]);
        if ($count) {
            echo json_encode(['status' => 9, 'info' => '您有订单还未完成', 'data' => null]);exit;
        }
        $count = $po->getCount(['uid' => $param['uid'], 'status' => ['in', '1,6,7']]);
        if ($count) {
            echo json_encode(['status' => 11, 'info' => '您有订单还未完成', 'data' => null]);exit;
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
        $order_num = $param['order_num'];
        $porder    = $po->getModel(['order_num' => $order_num], ['id', 'uid', 'order_num', 'game_id', 'region', 'para_id', 'num', 'price', 'type', 'addtime', 'order_money']);
        if (!$porder) {
            echo json_encode(['status' => 3, 'info' => '订单不存在', 'data' => null]);exit;
        }
        if (time() > $porder['addtime'] + 300) {
            echo json_encode(['status' => 5, 'info' => '订单已过期', 'data' => null]);exit;
        }
        if ($porder['type'] === 1) {
            $porder['type'] = '普通订单';
        }
        if (!empty($porder['addtime'])) {
            $porder['addtime'] = date('Y-m-d H:i:s', $porder['addtime']);
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
        if ($last_money > config('LOWERMONEY')) {
            $c   = new CouponModel();
            $cus = $c->getModel(['uid' => $porder['uid'], 'status' => 0], ['id', 'money']);
            if ($cus) {
                $last_money -= $cus['money'];
                $c->modifyField('status', 6, ['id' => $cus['id']]);
            }
        }
        $porder['last_money'] = $last_money;
        $po->modifyField('order_money', $last_money, ['order_num' => $order_num]);
        // $total_fee    = floatval($last_money);
        // $total_fee *= 100;
        $total_fee = 1;
        // 调用微信预支付
        $pay_data = $this->wxpay($porder['uid'], $order_num, $total_fee);
        if ($pay_data === false) {
            $msg = ['status' => 5, 'info' => '玩家不存在', 'data' => null];
        } elseif ($pay_data === 1) {
            $msg = ['status' => 6, 'info' => '连接服务器失败', 'data' => null];
        } elseif ($pay_data === 2) {
            $msg = ['status' => 7, 'info' => '预支付失败', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $data = ['uid' => $porder['uid'], 'type' => 1, 'money' => $last_money, 'addtime' => time()];
        $c    = new ConsumeModel();
        $c->add($data);
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => ['porder' => $porder, 'pay_data' => $pay_data]]);exit;
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
            $msg = ['status' => 1, 'info' => '订单ID不能为空', 'data' => null];
        }
        if (empty($param['uid'])) {
            $msg = ['status' => 2, 'info' => '下单人ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $count = $po->getCount(['id' => $param['order_id'], 'uid' => $param['uid']]);
        if (!$count) {
            echo json_encode(['status' => 4, 'info' => '非法操作', 'data' => null]);exit;
        }
        $pmo   = new PersonMasterOrderModel();
        $count = $pmo->getCount(['order_id' => $param['order_id']]);
        if ($count) {
            echo json_encode(['status' => 3, 'info' => '订单已被抢不能取消', 'data' => null]);exit;
        }
        $res = $po->modifyField('status', 3, ['id' => $param['order_id']]);
        if (!$res) {
            echo json_encode(['status' => 5, 'info' => '取消失败', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '取消成功，您的退款会在3个工作日原路返回到你的支付账户', 'data' => null]);exit;
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
            $msg = ['status' => 1, 'info' => '订单号不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uorder = $uo->getModel(['order_num' => $param['order_num']]);
        if (!$uorder) {
            echo json_encode(['status' => 3, 'info' => '订单不存在', 'data' => null]);exit;
        }
        $res = $uo->pay_money($uorder);
        if ($res !== true) {
            echo json_encode(['status' => $res, 'info' => '支付失败', 'data' => null]);exit;
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
            echo json_encode(['status' => 3, 'info' => '订单已过期', 'data' => null]);exit;
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
        $users  = [];
        $uids   = array_column($list, 'uid');
        if (!empty($uids)) {
            $u     = new UserModel();
            $users = $u->getList(['id' => ['in', $uids]], ['id', 'avatar']);
            $users = array_column($users, 'avatar', 'id');
        }
        foreach ($gconfs as $gco) {
            $gc_arr[$gco['game_id']][$gco['para_id']] = $gco['para_str'];
        }
        foreach ($list as &$item) {
            if (!empty($users[$item['uid']])) {
                $item['avatar'] = $users[$item['uid']];
            } else {
                $item['avatar'] = '';
            }
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
        $master_id = $param['master_id'];
        $r         = new RoomModel();
        $count     = $r->getCount(['uid' => $master_id, 'status' => ['in', '0,1,5,6,8']]);
        if ($count) {
            echo json_encode(['status' => 9, 'info' => '您已在房间游戏中', 'data' => null]);exit;
        }
        $rm    = new RoomMasterModel();
        $count = $rm->getCount(['uid' => $master_id]);
        if ($count) {
            echo json_encode(['status' => 9, 'info' => '您已在房间游戏中', 'data' => null]);exit;
        }
        $u     = new UserModel();
        $count = $u->getCount(['id' => $master_id, 'type' => 2, 'status' => 8]);
        if (!$count) {
            echo json_encode(['status' => 6, 'info' => '您还未认证成为陪玩师，请先认证', 'data' => null]);exit;
        }
        $po     = new PersonOrderModel();
        $porder = $po->getModel(['id' => $param['order_id']]);
        // 查询订制订单
        $ord_where = ['uid' => $master_id, 'game_id' => $porder['game_id'], 'status' => 8];
        $tip       = 'yule';
        if ($porder['play_type'] === 1) {
            $tip                    = 'shile';
            $ord_where['play_type'] = 1;
        }
        $ua    = new UserAttrModel();
        $count = $ua->getCount($ord_where);
        if (!$count) {
            echo json_encode(['status' => 7, 'info' => '您还未认证该游戏的陪玩类型', 'data' => $tip]);exit;
        }
        $count = $pmo->getList(['master_id' => $param['master_id'], 'status' => 0]);
        if ($count) {
            echo json_encode(['status' => 11, 'info' => '您还有未完成的订制订单', 'data' => null]);exit;
        }
        $pmcount = $pmo->getCount(['order_id' => $param['order_id']]);
        if ($pmcount) {
            echo json_encode(['status' => 1, 'info' => '订单已被抢', 'data' => null]);exit;
        }
        $param['addtime'] = time();
        // 抢单
        $res = $pmo->robbing_order($param);
        $msg = ['status' => $res, 'data' => null];
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
            $msg = ['status' => 1, 'info' => '陪玩师ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $where = ['uid' => $param['master_id']];
        if (!empty($param['status'])) {
            $where['status'] = $param['status'];
        } else {
            $where['status'] = ['not in', '3,9'];
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
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
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
            $msg = ['status' => 1, 'info' => '陪玩师ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $where = ['a.master_id' => $param['master_id']];
        if (!empty($param['status'])) {
            if (intval($param['status']) === 6) {
                $where['a.status'] = ['in', '7,8'];
            }
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
        $list = $pmo->getJoinList([['m_person_order po', ['a.order_id=po.id']]], $where, ['master_id', 'order_num', 'a.uid', 'game_id', 'play_type', 'order_money', 'a.addtime', 'a.status'], "$page,$pagesize");
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
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
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
        $list = $po->getList($where, ['order_num', 'uid', 'game_id', 'play_type', 'order_money', 'addtime', 'status'], "$page,$pagesize", 'addtime desc,status');
        if ($list) {
            $uids  = array_column($list, 'uid');
            $u     = new UserModel();
            $users = $u->getList(['type' => 1, 'id' => ['in', $uids]], ['id', 'nickname', 'avatar']);
            $users = array_column($users, null, 'id');
            $g     = new GameModel();
            $games = $g->getList(['is_delete' => 0], ['id', 'name']);
            $games = array_column($games, 'name', 'id');
            foreach ($list as &$item) {
                if (!empty($users[$item['uid']])) {
                    $item['nickname'] = $users[$item['uid']]['nickname'];
                    $item['avatar']   = $users[$item['uid']]['avatar'];
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
        $msg = ['status' => 0, 'info' => '获取成功', 'data' => $list];
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
            $msg = ['status' => 1, 'info' => '订单号不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $type = 2;
        if (!empty($param['type'])) {
            $type = 1;
        }
        $order_num = $param['order_num'];
        $porder    = $po->getModel(['order_num' => $order_num]);
        if (!$porder) {
            echo json_encode(['status' => 3, 'info' => '订单不存在', 'data' => null]);exit;
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
            if ($type === 2) {
                $porder['status'] = $pmorder['status'];
            }
            $user = $u->getModel(['id' => $pmorder['master_id']], ['avatar', 'nickname']);
            if ($user) {
                $porder['master_id']       = $pmorder['master_id'];
                $porder['master_avatar']   = $user['avatar'];
                $porder['master_nickname'] = $user['nickname'];
            }
        }
        // 属性赋值
        $porder['avatar'] = '';
        // 获取玩家信息
        $user = $u->getModel(['id' => $porder['uid']], ['avatar']);
        if ($user) {
            $porder['avatar'] = $user['avatar'];
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
        $comment = $ue->getModel(['type' => 2, 'order_id' => $porder['id']], ['uid', 'content', 'score', 'addtime']);
        if ($comment) {
            $user = $u->getModel(['id' => $comment['uid']], ['avatar', 'nickname']);
            // 属性赋值
            $comment['user_nickname'] = $user['nickname'];
            $comment['user_avatar']   = $user['avatar'];
            if ($comment['addtime']) {
                $comment['addtime'] = date('Y-m-d H:i:s', $comment['addtime']);
            }
        }
        $porder['comment'] = $comment;
        // 订单未支付
        $pay_data = null;
        if ($porder['status'] === 1) {
            $po->modifyField('addtime', time(), ['id' => $porder['id']]);
            $total_fee = $porder['order_money'] * 100;
            $total_fee = 1;
            // 调用微信支付
            $pay_data = $this->wxpay($porder['uid'], $order_num, $total_fee);
            if ($pay_data === false) {
                $msg = ['status' => 5, 'info' => '玩家不存在', 'data' => null];
            } elseif ($pay_data === 1) {
                $msg = ['status' => 6, 'info' => '连接服务器失败', 'data' => null];
            } elseif ($pay_data === 2) {
                $msg = ['status' => 7, 'info' => '预支付失败', 'data' => null];
            }
            if (!empty($msg)) {
                echo json_encode($msg);exit;
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => ['order' => $porder, 'pay_data' => $pay_data]]);exit;
    }

    /**
     * 获取订单状态
     * @author 贺强
     * @time   2018-12-07 20:48:51
     * @param  PersonOrderModel $po PersonOrderModel 实例
     */
    public function get_pord_status(PersonOrderModel $po)
    {
        $param = $this->param;
        if (empty($param['order_id'])) {
            $msg = ['status' => 1, 'info' => '订单ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $porder = $po->getModel(['id' => $param['order_id']], ['status']);
        if ($porder) {
            echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => ['status' => $porder['status']]]);exit;
        }
        echo json_encode(['status' => 4, 'info' => '订单不存在', 'data' => null]);exit;
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
            $msg = ['status' => 1, 'info' => '订单号不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $order_num = $param['order_num'];
        $uorder    = $uo->getModel(['order_num' => $order_num]);
        if (!$uorder) {
            echo json_encode(['status' => 3, 'info' => '订单不存在', 'data' => null]);exit;
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
        $u    = new UserModel();
        $user = $u->getModel(['id' => $uorder['uid']], ['avatar', 'nickname']);
        if ($user) {
            $uorder['avatar']   = $user['avatar'];
            $uorder['nickname'] = $user['nickname'];
        } else {
            $uorder['avatar']   = '';
            $uorder['nickname'] = '';
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
        $comment = $ue->getModel(['type' => 1, 'order_id' => $uorder['id']], ['uid', 'content', 'score', 'addtime']);
        if ($comment) {
            $user = $u->getModel(['id' => $comment['uid']], ['avatar', 'nickname']);
            // 属性赋值
            $comment['user_nickname'] = $user['nickname'];
            $comment['user_avatar']   = $user['avatar'];
            if ($comment['addtime']) {
                $comment['addtime'] = date('Y-m-d H:i:s', $comment['addtime']);
            }
        }
        $uorder['comment'] = $comment;
        // 如果未支付
        $pay_data = null;
        if ($uorder['status'] === 1) {
            $total_fee = $uorder['order_money'] * 100;
            $pay_data  = $this->wxpay($uorder['uid'], $order_num, $total_fee);
            if ($pay_data === false) {
                $msg = ['status' => 5, 'info' => '玩家不存在', 'data' => null];
            } elseif ($pay_data === 1) {
                $msg = ['status' => 6, 'info' => '连接服务器失败', 'data' => null];
            } elseif ($pay_data === 2) {
                $msg = ['status' => 7, 'info' => '预支付失败', 'data' => null];
            }
            if (!empty($msg)) {
                echo json_encode($msg);exit;
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => ['order' => $uorder, 'pay_data' => $pay_data]]);exit;
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
            $msg = ['status' => 1, 'info' => '订单号不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $morder = $mo->getModel(['order_num' => $param['order_num']]);
        if (!$morder) {
            echo json_encode(['status' => 3, 'info' => '订单不存在', 'data' => null]);exit;
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
        $uo      = new UserOrderModel();
        $comment = $uo->getJoinList([['m_user_evaluate ue', 'a.id=ue.order_id']], ['type' => 1, 'a.room_id' => $morder['room_id']], ['ue.uid', 'ue.content', 'ue.score', 'ue.addtime']);
        if ($comment) {
            $uids  = array_column($comment, 'uid');
            $users = $u->getList(['id' => ['in', $uids]], ['id', 'nickname', 'avatar']);
            $users = array_column($users, null, 'id');
            foreach ($comment as &$cmt) {
                if ($cmt['addtime']) {
                    $cmt['addtime'] = date('Y-m-d H:i:s', $cmt['addtime']);
                }
                if (!empty($users[$cmt['uid']])) {
                    $user = $users[$cmt['uid']];
                    // 属性赋值
                    $cmt['nickname'] = $user['nickname'];
                    $cmt['avatar']   = $user['avatar'];
                } else {
                    $cmt['nickname'] = '';
                    $cmt['avatar']   = '';
                }
            }
        }
        $morder['comment'] = $comment;
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $morder]);exit;
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
            $msg = ['status' => 1, 'info' => '用户ID不能为空', 'data' => null];
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
        $msg = ['status' => 0, 'info' => '获取成功', 'data' => $list];
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
            $msg = ['status' => 1, 'info' => '用户ID不能为空', 'data' => null];
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
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 陪玩师申请提现
     * @author 贺强
     * @time   2018-11-28 19:53:50
     * @param  MasterMoneyLogModel $mml MasterMoneyLogModel 实例
     */
    public function apply_cash(MasterMoneyLogModel $mml)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '陪玩师ID不能为空', 'data' => null];
        } elseif (empty($param['money'])) {
            $msg = ['status' => 3, 'info' => '申请提现金额不能为家', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $param['addtime'] = time();
        $res              = $mml->add($param);
        if (!$res) {
            echo json_encode(['status' => 5, 'info' => '申请失败，请联系平台', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '申请成功，提现金额会在3个工作日内打到您的微信账号里', 'data' => null]);exit;
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
            $msg = ['status' => 1, 'info' => '陪玩师ID不能为空', 'data' => null];
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
        $u    = new UserModel();
        $user = $u->getModel(['id' => $param['uid']], ['nickname', 'avatar', 'money']);
        if ($list) {
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
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => ['money' => $user['money'], 'log' => $list]]);exit;
    }

    /**
     * 前端回调修改订单状态
     * @author 贺强
     * @time   2018-12-13 15:06:08
     */
    public function pay_callback()
    {
        $param = $this->param;
        if (empty($param['room_id'])) {
            $msg = ['status' => 1, 'info' => '房间ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $room_id = $param['room_id'];
        $r       = new RoomModel();
        $room    = $r->getModel(['id' => $room_id], ['count']);
        $ru      = new RoomUserModel();
        $count   = $ru->getCount(['room_id' => $room_id, 'status' => 6]);
        if ($count === $room['count']) {
            $r->modifyField('status', 6, ['id' => $room_id]);
        }
        echo json_encode(['status' => 0, 'info' => '修改成功', 'data' => null]);exit;
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
     * 微信支付
     * @author 贺强
     * @time   2018-11-29 11:08:11
     * @param  integer $uid       用户ID
     * @param  string  $order_num 本系统订单号
     * @param  integer $total_fee 支付总金额
     * @param  string  $body      商品描述
     */
    private function wxpay($uid, $order_num, $total_fee = 1, $body = '游戏支付')
    {
        $u    = new UserModel();
        $user = $u->getModel(['id' => $uid], ['openid']);
        if (!$user) {
            return false;
        }
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        // 统一下单参数构造
        $nonce_str    = get_random_str(15);
        $unifiedorder = array(
            'appid'            => config('APPID_PLAYER'),
            'body'             => $body,
            'mch_id'           => config('PAY_MCHID'),
            'nonce_str'        => $nonce_str,
            'notify_url'       => config('WEBSITE') . '/api/pay/notify',
            'openid'           => $user['openid'],
            'out_trade_no'     => $order_num,
            'spbill_create_ip' => get_client_ip(),
            'total_fee'        => $total_fee,
            'trade_type'       => 'JSAPI',
        );
        $unifiedorder['sign'] = $this->make_sign($unifiedorder);
        // 数组转换为 xml
        $xmldata = array2xml($unifiedorder);
        $res     = $this->curl($url, $xmldata, false);
        if (!$res) {
            return 1;
        }
        $res = xml2array($res);
        if (strval($res['return_code']) == 'FAIL') {
            return 2;
            // var_dump($res['return_msg']);exit;
        }
        if (!empty($res['result_code']) && strval($res['result_code']) == 'FAIL') {
            return 2;
            // var_dump($res['err_code_des']);exit;
        }
        $pay_data = ['appId' => config('APPID_PLAYER'), 'nonceStr' => $res['nonce_str'], 'package' => 'prepay_id=' . $res['prepay_id'], 'signType' => 'MD5', 'timeStamp' => time()];
        // 计算签名
        $pay_data['paySign']   = $this->make_sign($pay_data);
        $pay_data['order_num'] = $order_num;
        return $pay_data;
    }

}
