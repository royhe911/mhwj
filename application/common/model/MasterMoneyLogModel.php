<?php
namespace app\common\model;

use think\Db;

/**
 * MasterMoneyLogModel类
 * @author 贺强
 * @time   2018-11-18 15:44:57
 */
class MasterMoneyLogModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_master_money_log';
    }

    /**
     * 陪玩师申请提现
     * @author 贺强
     * @time   2018-12-17 15:09:05
     * @param  array $param 提现参数
     */
    public function applyCash($param)
    {
        Db::startTrans();
        try {
            $u    = new UserModel();
            $user = $u->getModel(['id' => $param['uid']]);
            if (!$user) {
                return 10;
            }
            if ($param['money'] > $user['money']) {
                return 30;
            }
            $res = $u->decrement('money', ['id' => $user['id']], $param['money']);
            if (!$res) {
                Db::rollback();
                return 40;
            }
            $res = $this->add($param);
            if (!$res) {
                Db::rollback();
                return 50;
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return 44;
        }
    }
}
