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
            $data = $data[0];
            if ($data['status'] === 44) {
                return 11;
            }
            $pu    = new PrizeUserModel();
            $user  = $pu->getModel(['prize_id' => $prize_id], ['count(distinct uid) count']);
            $count = $user['count'];
            if ($count < $data['count']) {
                if (!empty($param['share_uid'])) {
                    $share_uid = $param['share_uid'];
                    $puser     = $pu->getModel(['prize_id' => $prize_id, 'uid' => $share_uid], ['form_id']);
                    if ($puser) {
                        $pu->add(['prize_id' => $prize_id, 'uid' => $share_uid, 'share_uid' => $param['uid'], 'code' => $param['share_code'], 'addtime' => time(), 'form_id' => $puser['form_id']]);
                    }
                }
                unset($param['share_code'], $param['share_uid']);
                $res = $pu->add($param);
                if (!$res) {
                    Db::rollback();
                    return 30;
                }
            } else {
                Db::rollback();
                return 20;
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
            var_dump($e->getMessage());
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
        $pu   = new PrizeUserModel();
        $data = $pu->getList(['prize_id' => $prize_id], ['id', 'uid', 'code', 'form_id']);
        shuffle($data);
        $num    = intval(count($data) / 2);
        $index  = mt_rand(0, count($data) - 1);
        $lucker = $data[$index];
        unset($data[$index]);
        $data  = array_merge($data);
        $ldata = ['code' => $lucker['code'], 'uid' => $lucker['uid'], 'prize_id' => $prize_id, 'addtime' => time()];
        $res   = $pd->add($ldata);
        if (!$res) {
            return 21;
        }
        $p = new PrizeModel();
        $p->modifyField('status', 44, ['id' => $prize_id]);
        $pu->modifyField('is_winners', 1, ['id' => $lucker['id']]);
        $ludt = [];
        for ($i = 0; $i < $num; $i++) {
            $index  = mt_rand(0, count($data) - 1);
            $ludt[] = ['uid' => $data[$index]['uid'], 'type' => 2, 'prize_id' => $prize_id, 'money' => 5, 'addtime' => time(), 'over_time' => time() + 15 * 24 * 3600];
            unset($data[$index]);
            $data = array_merge($data);
            shuffle($data);
        }
        $c = new CouponModel();
        $c->addArr($ludt);
        return ['prize_id' => $prize_id];
    }
}
