<?php
namespace app\common\crontab;

use app\common\model\RoomModel;
use app\common\model\UserOrderModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 娱乐订单定时任务类
 * @author 贺强
 * @time   2018-12-21 16:33:35
 */
class YuleOrder extends Command
{
    /**
     * 设置定时任务时间执行规则
     * @author 贺强
     * @time   2018-12-21 16:33:41
     */
    protected function configure()
    {
        $this->setName('yuleorder')->setDescription('here is the remark');
    }

    /**
     * 执行定时任务
     * @author 贺强
     * @time   2018-12-21 16:33:51
     */
    protected function execute(Input $input, Output $output)
    {
        $uo   = new UserOrderModel();
        $list = $uo->getList(['play_type' => 2, 'status' => 6, 'pay_time' => ['lt', time() - 300]], ['id', 'room_id']);
        if ($list) {
            $r    = new RoomModel();
            $rids = [];
            $ids  = [];
            foreach ($list as $item) {
                $rids[] = $item['room_id'];
                $ids[]  = $item['id'];
            }
            $r->modifyField('status', 7, ['id' => ['in', $rids]]);
            $uo->modifyField('status', 9, ['id' => ['in', $ids]]);
        }
    }
}
