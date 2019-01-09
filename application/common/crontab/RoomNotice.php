<?php
namespace app\common\crontab;

use app\common\model\RoomNoticeModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 删除进入房间提醒定时任务类
 * @author 贺强
 * @time   2019-01-09 14:33:16
 */
class RoomNotice extends Command
{
    /**
     * 设置定时任务时间执行规则
     * @author 贺强
     * @time   2019-01-09 14:33:25
     */
    protected function configure()
    {
        $this->setName('delroomnotice')->setDescription('here is the remark');
    }

    /**
     * 执行定时任务
     * @author 贺强
     * @time   2019-01-09 14:33:40
     */
    protected function execute(Input $input, Output $output)
    {
        $rn   = new RoomNoticeModel();
        $time = strtotime(date('Y-m-d'));
        $rn->delByWhere(['addtime' => ['lt', $time]]);
    }
}
