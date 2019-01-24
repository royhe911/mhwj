<?php
namespace app\common\crontab;

use app\common\model\TChatModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 删除聊天定时任务类
 * @author 贺强
 * @time   2019-01-24 20:24:19
 */
class Chat extends Command
{
    /**
     * 设置定时任务时间执行规则
     * @author 贺强
     * @time   2019-01-24 20:24:23
     */
    protected function configure()
    {
        $this->setName('delchatlog')->setDescription('here is the remark');
    }

    /**
     * 执行定时任务
     * @author 贺强
     * @time   2019-01-24 20:24:31
     */
    protected function execute(Input $input, Output $output)
    {
        $c     = new TChatModel();
        $start = time() - 172800;
        $where = ['addtime' => ['lt', $start], 'is_read' => 1];
        $c->delByWhere($where);
    }
}
