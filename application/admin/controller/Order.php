<?php
namespace app\admin\controller;

use app\common\model\GameModel;
use app\common\model\MasterMoneyLogModel;
use app\common\model\MasterOrderModel;
use app\common\model\PersonMasterOrderModel;
use app\common\model\PersonOrderModel;
use app\common\model\RoomModel;
use app\common\model\UserModel;
use app\common\model\UserOrderModel;

/**
 * Order-控制器
 * @author 贺强
 * @time   2018-11-22 11:59:34
 */
class Order extends \think\Controller
{
    /**
     * 房间用户订单
     * @author 贺强
     * @time   2018-11-22 14:11:50
     * @param  UserOrderModel $uo UserOrderModel 实例
     */
    public function uorders(UserOrderModel $uo)
    {
        $where = [];
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $uo->getList($where, true, "$page,$pagesize", 'addtime desc');
        if ($list) {
            $uids = array_column($list, 'uid');
            $gids = array_column($list, 'game_id');
            $u    = new UserModel();
            $user = $u->getList(['id' => ['in', $uids]], ['id', 'nickname']);
            $user = array_column($user, 'nickname', 'id');
            $g    = new GameModel();
            $game = $g->getList(['id' => ['in', $gids]], ['id', 'name']);
            $game = array_column($game, 'name', 'id');
            foreach ($list as &$item) {
                if (!empty($user[$item['uid']])) {
                    $item['nickname'] = $user[$item['uid']];
                } else {
                    $item['nickname'] = '';
                }
                if (!empty($game[$item['game_id']])) {
                    $item['gamename'] = $game[$item['game_id']];
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
                if (!empty($item['pay_time'])) {
                    $item['pay_time'] = date('Y-m-d H:i:s', $item['pay_time']);
                }
                switch ($item['status']) {
                    case 1:
                        $item['status_txt'] = '未支付';
                        break;
                    case 3:
                        $item['status_txt'] = '退单';
                        break;
                    case 6:
                        $item['status_txt'] = '已支付';
                        break;
                    case 10:
                        $item['status_txt'] = '已完成';
                        break;
                    default:
                        $item['status_txt'] = '';
                        break;
                }
            }
        }
        $count = $uo->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('uorders', ['list' => $list, 'pages' => $pages]);
    }

    /**
     * 订单详情
     * @author 贺强
     * @time   2018-11-22 15:50:02
     * @param  UserOrderModel $uo UserOrderModel 实例
     */
    public function udetail(UserOrderModel $uo)
    {
        $id    = $this->request->get('id');
        $order = $uo->getModel(['id' => $id]);
        if ($order) {
            $r    = new RoomModel();
            $u    = new UserModel();
            $user = $u->getModel(['id' => $order['uid']]);
            if ($user) {
                $order['user_nickname'] = $user['nickname'];
                $order['user_mobile']   = $user['mobile'];
            } else {
                $order['user_nickname'] = '';
                $order['user_mobile']   = '';
            }
            $room   = $r->getModel(['id' => $order['room_id']]);
            $master = null;
            if ($room) {
                $master = $u->getModel(['id' => $room['uid']]);
            }
            if ($master) {
                $order['master_nickname'] = $master['nickname'];
                $order['master_id']       = $master['id'];
                $order['master_mobile']   = $master['mobile'];
            } else {
                $order['master_nickname'] = '';
                $order['master_id']       = '';
                $order['master_mobile']   = '';
            }
        }
        return $this->fetch('udetail', ['order' => $order]);
    }

    /**
     * 订制订单
     * @author 贺强
     * @time   2018-11-22 14:12:45
     * @param  PersonOrderModel $po PersonOrderModel 实例
     */
    public function porders(PersonOrderModel $po)
    {
        $where = [];
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $po->getList($where, true, "$page,$pagesize", 'addtime desc');
        if ($list) {
            $uids = array_column($list, 'uid');
            $gids = array_column($list, 'game_id');
            $u    = new UserModel();
            $user = $u->getList(['id' => ['in', $uids]], ['id', 'nickname']);
            $user = array_column($user, 'nickname', 'id');
            $g    = new GameModel();
            $game = $g->getList(['id' => ['in', $gids]], ['id', 'name']);
            $game = array_column($game, 'name', 'id');
            foreach ($list as &$item) {
                if (!empty($user[$item['uid']])) {
                    $item['nickname'] = $user[$item['uid']];
                } else {
                    $item['nickname'] = '';
                }
                if (!empty($game[$item['game_id']])) {
                    $item['gamename'] = $game[$item['game_id']];
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
                    case 3:
                        $item['status_txt'] = '退单';
                        break;
                    case 6:
                        $item['status_txt'] = '已支付';
                        break;
                    case 10:
                        $item['status_txt'] = '已完成';
                        break;
                    default:
                        $item['status_txt'] = '';
                        break;
                }
            }
        }
        $count = $po->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('porders', ['list' => $list, 'pages' => $pages]);
    }

    /**
     * 订制订单详情
     * @author 贺强
     * @time   2018-11-22 17:45:21
     * @param  PersonOrderModel $po PersonOrderModel 实例
     */
    public function pdetail(PersonOrderModel $po)
    {
        $id    = $this->request->get('id');
        $order = $po->getModel(['id' => $id]);
        if ($order) {
            $pmo  = new PersonMasterOrderModel();
            $u    = new UserModel();
            $user = $u->getModel(['id' => $order['uid']]);
            if ($user) {
                $order['user_nickname'] = $user['nickname'];
                $order['user_mobile']   = $user['mobile'];
            } else {
                $order['user_nickname'] = '';
                $order['user_mobile']   = '';
            }
            $pmorder = $pmo->getModel(['order_id' => $order['id']]);
            $master  = null;
            if ($pmorder) {
                $master = $u->getModel(['id' => $pmorder['master_id']]);
            }
            if ($master) {
                $order['master_nickname'] = $master['nickname'];
                $order['master_id']       = $master['id'];
                $order['master_mobile']   = $master['mobile'];
            } else {
                $order['master_nickname'] = '';
                $order['master_id']       = '';
                $order['master_mobile']   = '';
            }
        }
        return $this->fetch('pdetail', ['order' => $order]);
    }

    /**
     * 陪玩师订单列表
     * @author 贺强
     * @time   2018-11-22 17:51:55
     * @param  MasterOrderModel $mo MasterOrderModel 实例
     */
    public function morders(MasterOrderModel $mo)
    {
        $where = [];
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $mo->getList($where, true, "$page,$pagesize", 'addtime desc');
        if ($list) {
            $uids = array_column($list, 'uid');
            $gids = array_column($list, 'game_id');
            $u    = new UserModel();
            $user = $u->getList(['id' => ['in', $uids]], ['id', 'nickname']);
            $user = array_column($user, 'nickname', 'id');
            $g    = new GameModel();
            $game = $g->getList(['id' => ['in', $gids]], ['id', 'name']);
            $game = array_column($game, 'name', 'id');
            foreach ($list as &$item) {
                if (!empty($user[$item['uid']])) {
                    $item['nickname'] = $user[$item['uid']];
                } else {
                    $item['nickname'] = '';
                }
                if (!empty($game[$item['game_id']])) {
                    $item['gamename'] = $game[$item['game_id']];
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
                    case 3:
                        $item['status_txt'] = '退单';
                        break;
                    case 6:
                        $item['status_txt'] = '已支付';
                        break;
                    case 10:
                        $item['status_txt'] = '已完成';
                        break;
                    default:
                        $item['status_txt'] = '';
                        break;
                }
            }
        }
        $count = $mo->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('morders', ['list' => $list, 'pages' => $pages]);
    }

    /**
     * 陪玩师订单详情
     * @author 贺强
     * @time   2018-11-22 18:00:43
     * @param  MasterOrderModel $mo MasterOrderModel 实例
     */
    public function mdetail(MasterOrderModel $mo)
    {
        $id    = $this->request->get('id');
        $order = $mo->getModel(['id' => $id]);
        if ($order) {
            $u      = new UserModel();
            $master = $u->getModel(['id' => $order['uid']]);
            if ($master) {
                $order['master_nickname'] = $master['nickname'];
                $order['master_id']       = $master['id'];
                $order['master_mobile']   = $master['mobile'];
            } else {
                $order['master_nickname'] = '';
                $order['master_id']       = '';
                $order['master_mobile']   = '';
            }
        }
        return $this->fetch('mdetail', ['order' => $order]);
    }

    /**
     * 更改订单状态
     * @author 贺强
     * @time   2018-11-22 16:51:59
     */
    public function complete()
    {
        $param = $this->request->post();
        $type  = intval($param['type']);
        if ($type === 1) {
            $model = new UserOrderModel();
        } elseif ($type === 2) {
            $model = new PersonOrderModel();
        } elseif ($type === 3) {
            $model = new MasterOrderModel();
        }
        $res = $model->modifyField('status', $param['status'], ['id' => $param['id']]);
        if ($res !== false) {
            return ['status' => 0, 'info' => '修改成功'];
        } else {
            return ['status' => 4, 'info' => '修改失败'];
        }
    }

    /**
     * 申请提现列表
     * @author 贺强
     * @time   2018-11-28 20:05:09
     * @param  MasterMoneyLogModel $mml MasterMoneyLogModel 实例
     */
    public function cash_list(MasterMoneyLogModel $mml)
    {
        $where = [];
        // 分页参数
        $page     = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', config('PAGESIZE'));
        $list     = $mml->getList($where, true, "$page,$pagesize", 'status desc,addtime desc');
        if ($list) {
            $uids = array_column($list, 'uid');
            $u    = new UserModel();
            $user = $u->getList(['id' => ['in', $uids]], 'id,nickname');
            $user = array_column($user, 'nickname', 'id');
            foreach ($list as &$item) {
                if (!empty($user[$item['uid']])) {
                    $item['nickname'] = $user[$item['uid']];
                } else {
                    $item['nickname'] = '';
                }
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
                switch ($item['status']) {
                    case 1:
                        $item['status_txt'] = '申请中';
                        break;
                    case 4:
                        $item['status_txt'] = '审核不通过';
                        break;
                    case 8:
                        $item['status_txt'] = '已提现';
                        break;
                    default:
                        $item['status_txt'] = '';
                        break;
                }
            }
        }
        $count = $mml->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('cashlist', ['list' => $list, 'pages' => $pages]);
    }

    /**
     * 提现详情
     * @author 贺强
     * @time   2018-11-28 20:22:41
     * @param  MasterMoneyLogModel $mml MasterMoneyLogModel 实例
     */
    public function cash_detail(MasterMoneyLogModel $mml)
    {
        $id   = $this->request->get('id');
        $cash = $mml->getModel(['id' => $id]);
        if ($cash) {
            $u    = new UserModel();
            $user = $u->getModel(['id' => $cash['uid']]);
            if ($user) {
                $cash['avatar']   = $user['avatar'];
                $cash['nickname'] = $user['nickname'];
                $cash['mobile']   = $user['mobile'];
                $cash['openid']   = $user['openid'];
            } else {
                $cash['avatar']   = '';
                $cash['nickname'] = '';
                $cash['mobile']   = '';
            }
            if (!empty($cash['addtime'])) {
                $cash['addtime'] = date('Y-m-d H:i:s', $cash['addtime']);
            }
            if (!empty($cash['auditor_time'])) {
                $cash['auditor_time'] = date('Y-m-d H:i:s', $cash['auditor_time']);
            }
            switch ($cash['status']) {
                case 1:
                    $cash['status_txt'] = '申请中';
                    break;
                case 4:
                    $cash['status_txt'] = '审核不通过';
                    break;
                case 8:
                    $cash['status_txt'] = '已提现';
                    break;
                default:
                    $cash['status_txt'] = '';
                    break;
            }
        }
        return $this->fetch('cashdetail', ['cash' => $cash]);
    }

    /**
     * 提现审核
     * @author 贺强
     * @time   2018-11-28 20:51:04
     * @param  MasterMoneyLogModel $mml MasterMoneyLogModel 实例
     */
    public function auditors(MasterMoneyLogModel $mml)
    {
        $param = $this->request->post();
        if (empty($param['id']) || empty($param['status'])) {
            return ['status' => 3, 'info' => '非法操作'];
        }
        $log = $mml->getModel(['id' => $param['id']]);
        if (!$log) {
            return ['status' => 1, 'info' => '提现记录不存在'];
        }
        $u    = new UserModel();
        $user = $u->getModel(['id' => $log['uid']]);
        if (!$user) {
            return ['status' => 5, 'info' => '用户不存在'];
        }
        $status = intval($param['status']);
        $trans  = false;
        if ($status === 8) {
            $amount = floatval($log['money']) * 100;
            // 测试提现金额
            $amount = 1;
            $trans  = $this->transfers($log['order_num'], $user['openid'], $amount);
        }
        if ($trans !== true) {
            return ['status' => $trans, 'info' => '打款失败'];
        }
        $param['auditor_time'] = time();
        // 保存数据
        $res = $mml->modify($param, ['id' => $param['id']]);
        if (!$res) {
            return ['status' => 4, 'info' => '审核失败'];
        }
        return ['status' => 0, 'info' => '审核成功'];
    }

    /**
     * 微信打款
     * @author 贺强
     * @time   2018-12-17 16:00:36
     * @param  string $order_num 商户订单号
     * @param  string $openid    用户微信openid
     * @param  string $username  玩家真实姓名
     * @param  int    $amount    提现金额
     */
    private function transfers($order_num, $openid, $username, $amount)
    {
        $url       = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $nonce_str = get_random_str(15);
        $transfers = [
            'amount'           => $amount,
            'check_name'       => 'FORCE_CHECK',
            'desc'             => '陪玩师提现',
            'mchid'            => config('PAY_MCHID'),
            'mch_appid'        => config('APPID_PLAYER'),
            'nonce_str'        => $nonce_str,
            'openid'           => $openid,
            'partner_trade_no' => $order_num,
            'spbill_create_ip' => get_client_ip(),
        ];
        // 生成签名
        $transfers['sign'] = make_sign($transfers);
        // 数组转xml
        $xmldata = array2xml($transfers);
        $res     = $this->curl($url, $xmldata, false);
        if (!$res) {
            return 1;
        }
        $res = xml2array($res);
        if (strval($res['return_code']) === 'FAIL') {
            return 2;
        }
        if (!empty($res['result_code']) && strval($res['result_code'])) {
            return 2;
        }
        return true;
    }
}
