<?php
namespace app\common\crontab;

use app\common\model\UserEvaluateModel;
use app\common\model\UserModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 更新陪玩师综合评分定时任务类
 * @author 贺强
 * @time   2019-01-08 14:23:02
 */
class MasterScore extends Command
{
    /**
     * 设置定时任务时间执行规则
     * @author 贺强
     * @time   2019-01-08 14:23:05
     */
    protected function configure()
    {
        $this->setName('masterscore')->setDescription('here is the remark');
    }

    /**
     * 执行定时任务
     * @author 贺强
     * @time   2019-01-08 14:23:36
     */
    protected function execute(Input $input, Output $output)
    {
        $ue   = new UserEvaluateModel;
        $list = $ue->getList([], ['sum(score) score', 'count(*) count', 'master_id'], null, '', 'master_id');
        if ($list) {
            $u = new UserModel();
            foreach ($list as $item) {
                $score = round($item['score'] / $item['count'], 2);
                $u->modifyField('score', $score, ['id' => $item['master_id']]);
            }
        }
    }
}
