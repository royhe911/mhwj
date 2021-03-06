<?php
namespace app\common\model;

use think\Db;

/**
 * GoodsTaskModel类
 * @author 贺强
 * @time   2018-12-11 09:20:19
 */
class GoodsTaskModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_goods_task';
    }

    /**
     * 发起砍价
     * @author 贺强
     * @time   2018-12-11 09:46:06
     * @param  array $task     任务数据
     * @param  array $taskInfo 任务详情数据
     */
    public function launch($task, $taskInfo)
    {
        Db::startTrans();
        try {
            $tid = $this->add($task);
            if (!$tid) {
                Db::rollback();
                return 1;
            }
            $g = new GoodsModel();
            $g->increment('count', ['id' => $task['goods_id']]);
            $gti = new GoodsTaskInfoModel();
            foreach ($taskInfo as &$info) {
                $info['task_id'] = $tid;
            }
            $res = $gti->addArr($taskInfo);
            if (!$res) {
                Db::rollback();
                return 3;
            }
            Db::commit();
            return ['tid' => $tid];
        } catch (\Exception $e) {
            Db::rollback();
            // var_dump($e->getMessage());
            // print_r($task);
            return 44;
        }
    }
}
