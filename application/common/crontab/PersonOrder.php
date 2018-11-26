<?php
namespace app\common\crontab;

use app\common\model\PersonOrderModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 订制订单定时任务类
 * @author 贺强
 * @time   2018-11-26 18:00:46
 */
class PersonOrder extends Command
{
    /**
     * 设置定时任务时间执行规则
     * @author 贺强
     * @time   2018-11-26 18:00:49
     */
    protected function configure()
    {
        $this->setName('person_order')->setDescription('here is the remark');
    }

    /**
     * 执行定时任务
     * @author 贺强
     * @time   2018-11-26 18:01:08
     */
    protected function execute(Input $input, Output $output)
    {
        $po   = new PersonOrderModel();
        $list = $po->getList(['status' => 1, 'addtime' => ['lt', time() - 300]], ['id']);
        foreach ($list as $item) {
            $po->modify('status', 4, ['id' => $item['id']]);
        }
    }
}
