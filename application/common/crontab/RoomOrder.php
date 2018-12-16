<?php
namespace app\common\crontab;

use app\common\model\UserOrderModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 房间订单定时任务类
 * @author 贺强
 * @time   2018-12-16 11:46:01
 */
class RoomOrder extends Command
{
    /**
     * 设置定时任务时间执行规则
     * @author 贺强
     * @time   2018-12-16 11:46:04
     */
    protected function configure()
    {
        $this->setName('roomorder')->setDescription('here is the remark');
    }

    /**
     * 执行定时任务
     * @author 贺强
     * @time   2018-12-16 11:46:13
     */
    protected function execute(Input $input, Output $output)
    {
        $uo   = new UserOrderModel();
        $list = $uo->getList(['status' => 6, 'addtime' => ['lt', time() - 3 * 24 * 3600]], ['id']);
        if ($list) {
            $ids = [];
            foreach ($list as $item) {
                $ids.=",{}"
            }
        }
    }
}
