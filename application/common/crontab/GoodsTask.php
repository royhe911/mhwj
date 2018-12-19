<?php
namespace app\common\crontab;

use app\common\model\GoodsTaskModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 砍价领奖定时任务类
 * @author 贺强
 * @time   2018-12-19 19:31:09
 */
class GoodsTask extends Command
{
    /**
     * 设置定时任务时间执行规则
     * @author 贺强
     * @time   2018-12-19 19:31:13
     */
    protected function configure()
    {
        $this->setName('goodstask')->setDescription('here is the remark');
    }

    /**
     * 执行定时任务
     * @author 贺强
     * @time   2018-12-19 19:31:21
     */
    protected function execute(Input $input, Output $output)
    {
        $gt   = new GoodsTaskModel();
        $list = $gt->getList(['status' => 8, 'valid_date' => ['lt', time()]], ['id']);
        if ($list) {
            $ids = [];
            foreach ($list as $item) {
                $ids[] = $item['id'];
            }
            if (!empty($ids)) {
                $gt->modifyField('status', 4, ['id' => ['in', $ids]]);
            }
        }
    }
}
