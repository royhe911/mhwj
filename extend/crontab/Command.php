<?php
/**
 * 创建者： 伏伟
 * 创建时间： 2018-06-04
 * 定时任务处理
 */
namespace crontab;

use think\console\Input;
use think\console\Output;

class Command extends \think\console\Command
{
    protected function configure()
    {
        $this->setName('crontab')->setDescription('this is a mini crontab manager tool!');
    }

    protected function execute(Input $input, Output $output)
    {
        $crontab = new \crontab\Dispatcher(RUNTIME_PATH . '/crontab_cache/', APP_PATH . '/common/crontab/');
        $crontab->addTask();
        $crontab->boot();
    }
}