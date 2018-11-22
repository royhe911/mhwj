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
            $mo     = new MasterOrderModel();
            $morder = $mo->getModel(['room_id' => $uorder['room_id']]);
            $mdat   = ['complete_money' => $morder['complete_money'] + $uorder['order_money'], 'complete_time' => time()];
            $cmy    = floatval($mdat['complete_money']);
            $omy    = floatval($morder['order_money']);
            if ($omy === $cmy) {
                $mdat['status'] = 6;
                $r              = new RoomModel();
                $r->modifyField('status', 8, ['id' => $uorder['room_id']]);
            }
            // 修改陪玩师订单完成金额和状态
            $res = $mo->modify($mdat, ['id' => $morder['id']]);
            if (!$res) {
                Db::rollback();
                return 9;
            }
            // 修改玩家房间状态
            $ru  = new RoomUserModel();
            $res = $ru->modifyField('status', 6, ['uid' => $uorder['uid'], 'room_id' => $uorder['room_id']]);
            if ($res === false) {
                Db::rollback();
                return 10;
            }
            // 更新玩家贡献值
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
            $uo  = new UserOrderModel();
            $res = $uo->modify(['status' => 6, 'pay_time' => time()], ['order_num' => $uorder['order_num']]);
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
