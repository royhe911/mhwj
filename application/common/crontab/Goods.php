<?php
namespace app\common\crontab;

use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 商品今日发起定时任务类
 * @author 贺强
 * @time   2018-12-11 16:38:47
 */
class Goods extends Command
{
    /**
     * 设置定时任务时间执行规则
     * @author 贺强
     * @time   2018-12-11 16:38:51
     */
    protected function configure()
    {
        $this->setName('goods')->setDescription('here is the remark');
    }

    /**
     * 执行定时任务
     * @author 贺强
     * @time   2018-12-11 16:38:57
     */
    protected function execute(Input $input, Output $output)
    {
        
    }
}