<?php
namespace app\common\crontab;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class Test extends Command
{
    protected function configure()
    {
        $this->setName('test')->setDescription('here is the remark');
    }

    protected function execute(Input $input, Output $output)
    {
        file_put_contents('/www/wwwroot/wwwdragontangcom/test.log','come in');
    }
}