<?php
namespace app\common\model;

use think\Db;

class PaylogModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 's1s_pay_log';
    }

    /**
     * 佣金审核
     * @Author 贺强
     * @date   2018-09-05
     * @param  integer    $id     条目ID
     * @param  integer    $status 状态
     */
    public function auditor($id, $status)
    {
        $pay = $this->getModel(['id' => $id]);
        if (!$pay) {
            return 1;
        }
        Db::startTrans();
        // 更新申请记录状态
        $res = $this->modify(['status' => $status, 'auditor_time' => time()], ['id' => $id]);
        if (!$res) {
            Db::rollback();
            return 2;
        }
        $a = new AdminModel();
        // 客服主管余额增加
        $res = $a->increment('balance', ['id' => $pay['uid']], $pay['money']);
        if (!$res) {
            Db::rollback();
            return 3;
        }
        // 取得财务 ID
        $model = $a->getModel(['role_id' => 6, 'is_delete' => 0], 'id');
        // 财务余额减少
        $res = $a->decrement('balance', ['id' => $model['id']], $pay['money']);
        if (!$res) {
            Db::rollback();
            return 4;
        }
        Db::commit();
        return true;
    }

    /**
     * 客服充值
     * @Author 贺强
     * @date   2018-09-06
     * @param  array      $param 充值参数
     */
    public function cwpay($param = [])
    {
        $a     = new AdminModel();
        $model = $a->getModel(['is_delete' => 0, 'role_id' => 6], 'id,balance');
        if (empty($model)) {
            return 1;
        }
        $after_money           = $model['balance'] + $param['money'];
        $param['addtime']      = time();
        $param['before_money'] = $model['balance'];
        $param['after_money']  = $after_money;
        $param['type']         = 1;
        Db::startTrans();
        $res = $this->add($param);
        if (!$res) {
            Db::rollback();
            return 2;
        }
        $res = $a->modifyField('balance', $after_money, ['id' => $model['id']]);
        if (!$res) {
            Db::rollback();
            return 3;
        }
        Db::commit();
        return true;
    }
}
