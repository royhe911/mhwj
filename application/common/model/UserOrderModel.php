<?php
namespace app\common\model;

use think\Db;

/**
 * UserOrderModel类
 * @author 贺强
 * @time   2018-10-26 16:23:06
 */
class UserOrderModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_user_order';
    }

    /**
     * 修改玩家订单
     * @author 贺强
     * @time   2018-11-15 14:44:06
     * @param  array  $data 要修改的数据
     */
    public function modify_order($data)
    {
        Db::startTrans();
        try {
            $uorder = $this->getModel(['order_num' => $data['order_num']]);
            if (!$uorder) {
                DB::rollback();
                return 10;
            }
            $sql    = "select * from m_master_order where id={$uorder['morder_id']} for update";
            $morder = Db::query($sql);
            if (!$morder) {
                Db::rollback();
                return 33;
            }
            $morder = $morder[0];
            $mdat   = ['complete_money' => $morder['complete_money'] + $uorder['order_money'], 'complete_time' => time()];
            if ($morder['order_money'] == $mdat['complete_money']) {
                $mdat['status'] = 6;
            }
            $mo  = new MasterOrderModel();
            $res = $mo->modify($mdat, ['id' => $uorder['morder_id']]);
            if (!$res) {
                Db::rollback();
                return 20;
            }
            $res = $this->modify($data, ['order_num' => $data['order_num']]);
            if (!$res) {
                Db::rollback();
                return 30;
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return 40;
        }
    }

    /**
     * 玩家房间订单支付
     * @author 贺强
     * @time   2018-11-21 16:18:33
     * @param  model  $uorder 玩家订单实例
     */
    public function pay_money($uorder)
    {
        Db::startTrans();
        try {
            $mo  = new MasterOrderModel();
            $res = $mo->increment('complete_money', $uorder['order_money'], ['room_id' => $uorder['room_id']]);
            if (!$res) {
                Db::rollback();
                return 10;
            }
            $morder = $mo->getModel(['room_id' => $uorder['room_id']]);
            if ($morder['order_money'] === $morder['complete_money']) {
                $r = new RoomModel();
                $r->modifyField('status', 8, ['id' => $uorder['room_id']]);
            }
            $contribution = $uorder['order_money'] * 100;
            $u            = new UserModel();
            if ($contribution > 0) {
                $u->increment('contribution', ['id' => $uorder['uid']], $contribution);
            }
            $data = ['uid' => $uorder['uid'], 'type' => 1, 'money' => $uorder['order_money'], 'addtime' => time()];
            $c    = new ConsumeModel();
            $res  = $c->add($data);
            if (!$res) {
                Db::rollback();
                return 20;
            }
            $res = $uo->modifyField('status', 6, ['order_num' => $param['order_num']]);
            if (!$res) {
                Db::rollback();
                return 30;
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return 44;
        }
    }
}
