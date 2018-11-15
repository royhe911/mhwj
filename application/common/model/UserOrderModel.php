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
            $sql    = "select * from m_master_order where id={$data['morder_id']} for update";
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
}
