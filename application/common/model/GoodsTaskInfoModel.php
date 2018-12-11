<?php
namespace app\common\model;

use think\Db;

/**
 * GoodsTaskInfoModel类
 * @author 贺强
 * @time   2018-12-11 09:21:16
 */
class GoodsTaskInfoModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_goods_task_info';
    }

    /**
     * 帮砍
     * @author 贺强
     * @time   2018-12-11 11:54:44
     * @param  array $param 参数
     */
    public function helpChop($param)
    {
        Db::startTrans();
        try {
            $task_id = $param['task_id'];
            $where   = ['status' => 1, 'task_id' => $task_id, 'is_use' => 0, 'is_baodao' => 0];
            if (!empty($param['is_baodao']) && intval($param['is_baodao']) === 1) {
                $where['is_baodao'] = 1;
            }
            // 查询是否还有非宝刀砍价
            $count = $this->getCount($where);
            if (!$count) {
                $where['is_baodao'] = 1;
            }
            $info = $this->getModel($where, ['id']);
            if (!$info) {
                Db::rollback();
                return 40;
            }
            // 防并发查询
            $id   = $info['id'];
            $sql  = "select id,price from m_goods_task_info where id=$id for update";
            $data = Db::query($sql);
            if (!$data) {
                Db::rollback();
                return 10;
            }
            $data = $data[0];
            // 属性赋值
            $data['uid']     = $param['uid'];
            $data['addtime'] = time();
            $data['is_use']  = 1;
            // 修改砍价
            $res = $this->modify($data, ['id' => $id]);
            if (!$res) {
                Db::rollback();
                return 20;
            }
            $count = $this->getCount(['task_id' => $task_id, 'is_use' => 0]);
            if (!$count) {
                $gt  = new GoodsTaskModel();
                $res = $gt->modifyField('status', 8, ['id' => $task_id]);
                if (!$res) {
                    Db::rollback();
                    return 30;
                }
                // 如果已砍完，则修改任务状态为已完成
                $res = $this->modifyField('status', 8, ['task_id' => $task_id]);
                if (!$res) {
                    Db::rollback();
                    return 30;
                }
            }
            Db::commit();
            return $data;
        } catch (\Exception $e) {
            Db::rollback();
            return 44;
        }
    }
}
