<?php
namespace app\common\model;

use think\Db;

/**
 * PersonMasterOrderModel类
 * @author 贺强
 * @time   2018-11-15 20:34:22
 */
class PersonMasterOrderModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_person_master_order';
    }

    /**
     * 陪玩师抢玩家订制订单
     * @author 贺强
     * @time   2018-11-15 20:42:22
     * @param  array $data 抢单数据
     */
    public function robbing_order($data)
    {
        Db::startTrans();
        try {
            $sql    = "select * from m_person_order where id={$data['order_id']} for update";
            $porder = Db::query($sql);
            if (!$porder) {
                Db::rollback();
                return 1;
            }
            $porder = $porder[0];
            if ($porder['status'] === 3) {
                return 2;
            }
            $res = $this->add($data);
            if (!$res) {
                Db::rollback();
                return 4;
            }
            $po  = new PersonOrderModel();
            $res = $po->modifyField('status', 7, ['id' => $porder['id']]);
            if (!$res) {
                Db::rollback();
                return 6;
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return 44;
        }
    }
}
