<?php
namespace app\common\crontab;

use app\common\model\VericodeModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 验证码定时任务类
 * @author 贺强
 * @time   2018-12-21 10:47:42
 */
class Vericode extends Command
{
    /**
     * 设置定时任务时间执行规则
     * @author 贺强
     * @time   2018-12-21 10:47:45
     */
    protected function configure()
    {
        $this->setName('vericode')->setDescription('here is the remark');
    }

    /**
     * 执行定时任务
     * @author 贺强
     * @time   2018-12-21 10:47:54
     */
    protected function execute(Input $input, Output $output)
    {
        $v = new VericodeModel();
        $v->delByWhere(['is_used' => 1, 'addtime' => ['lt', time()]]);
    }
}
