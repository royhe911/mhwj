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
            $prize_id = $param['prize_id'];
            // 锁定行查询
            $sql  = "select * from m_prize where id=$prize_id for update";
            $data = Db::query($sql);
            if (!$data) {
                Db::rollback();
                return 10;
            }
            $data  = $data[0];
            $pu    = new PrizeUserModel();
            $user  = $pu->getList(['prize_id' => $prize_id], ['distinct uid']);
            $count = count($user);
            if ($count < $data['count']) {
                $res = $pu->add($param);
                if (!$res) {
                    Db::rollback();
                    return 30;
                }
            }
            if ($count + 1 >= $data['count']) {
                $luck = $this->luck_draw($prize_id);
            }
            if (!empty($luck)) {
                if (is_array($luck)) {
                    $luck['prize_name'] = $data['name'];
                    Db::commit();
                } else {
                    Db::rollback();
                }
                return $luck;
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return 44;
        }
    }

    /**
     * 执行抽奖
     * @author 贺强
     * @time   2018-12-27 10:59:16
     * @param  integer $prize_id 奖品ID
     */
    public function luck_draw($prize_id)
    {
        $pd    = new PrizeDistributeModel();
        $count = $pd->getCount(['prize_id' => $prize_id]);
        if ($count) {
            return 11;
        }
        $pu     = new PrizeUserModel();
        $data   = $pu->getList(['prize_id' => $prize_id], ['uid', 'code', 'form_id']);
        $index  = mt_rand(0, count($data) - 1);
        $lucker = $data[$index];
        $ldata  = ['uid' => $lucker['uid'], 'prize_id' => $prize_id, 'addtime' => time()];
        $res    = $pd->add($ldata);
        if (!$res) {
            return 21;
        }
        $u    = new UserModel();
        $user = $u->getModel(['id' => $lucker['uid']], ['openid']);
        return ['openid' => $user['openid'], 'code' => $lucker['code'], 'form_id' => $lucker['form_id']];
    }
}
