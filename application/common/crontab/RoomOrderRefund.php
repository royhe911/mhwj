<?php
namespace app\common\crontab;

use app\common\model\RoomModel;
use app\common\model\UserOrderModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 用户付款5分钟后还没开车就自动退款定时任务类
 * @author 贺强
 * @time   2019-01-03 15:26:30
 */
class RoomOrderRefund extends Command
{
    /**
     * 设置定时任务时间执行规则
     * @author 贺强
     * @time   2019-01-03 15:26:33
     */
    protected function configure()
    {
        $this->setName('roomorderrefund')->setDescription('here is the remark');
    }

    /**
     * 执行定时任务
     * @author 贺强
     * @time   2019-01-03 15:26:49
     */
    protected function execute(Input $input, Output $output)
    {
        $uo   = new UserOrderModel();
        $list = $uo->getList(['status' => 6, 'pay_time' => ['lt', time() - 300]], ['room_id']);
        if ($list) {
            $ids = [];
            foreach ($list as $item) {
                $ids[] = $item['room_id'];
            }
            $r = new RoomModel();
            $r->modifyField('status', 7, ['id' => ['in', $ids]]);
        }
    }
}
