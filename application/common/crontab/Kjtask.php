<?php
namespace app\common\crontab;

use app\common\model\GoodsTaskInfoModel;
use app\common\model\GoodsTaskModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 砍价定时任务类
 * @author 贺强
 * @time   2018-12-11 11:56:00
 */
class Kjtask extends Command
{
    /**
     * 设置定时任务时间执行规则
     * @author 贺强
     * @time   2018-12-11 11:56:03
     */
    protected function configure()
    {
        $this->setName('kjtask')->setDescription('here is the remark');
    }

    /**
     * 执行定时任务
     * @author 贺强
     * @time   2018-12-11 11:56:09
     */
    protected function execute(Input $input, Output $output)
    {
        $gt   = new GoodsTaskModel();
        $list = $gt->getList(['status' => 1], ['id', 'addtime']);
        if ($list) {
            $ids = [];
            foreach ($list as $item) {
                $addtime = $item['addtime'] + 24 * 3600;
                if ($addtime < time()) {
                    $ids[] = $item['id'];
                }
            }
            if (!empty($ids)) {
                $gt->modifyField('status', 4, ['id' => ['in', $ids]]);
                $gti = new GoodsTaskInfoModel();
                $gti->modifyField('status', 4, ['task_id' => ['in', $ids]]);
            }
        }
    }
}
