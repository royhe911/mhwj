<?php
namespace app\common\model;

use think\Db;

/**
 * PrizeModel类
 * @author 贺强
 * @time   2018-12-26 16:08:29
 */
class PrizeModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_prize';
    }

    /**
     * 事务插入参与抽奖者信息，防并发
     * @author 贺强
     * @time   2018-12-26 17:05:18
     * @param  array $param 插入参数
     */
    public function joinPrize($param)
    {
        Db::startTrans();
        try {
            // 锁定行查询
            $sql  = "select * from m_prize where id={$param['prize_id']} for update";
            $data = Db::query($sql);
            if (!$data) {
                Db::rollback();
                return 10;
            }
            $data = $data[0];
            $pu   = new PrizeUserModel();
            $user = $pu->getList(['prize_id' => $param['prize_id']], ['distinct uid']);
            if (count($user) >= $data['count']) {
                Db::rollback();
                return 20;
            }
            $res = $pu->add($param);
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
