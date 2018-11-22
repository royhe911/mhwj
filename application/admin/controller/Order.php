<?php
namespace app\admin\controller;

use app\common\model\GameModel;
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
            $u    = new UserModel();
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
        }
        $res = $model->modifyField('status', $param['status'], ['id' => $param['id']]);
        if ($res !== false) {
            return ['status' => 0, 'info' => '修改成功'];
        } else {
            return ['status' => 4, 'info' => '修改失败'];
        }
    }
}
