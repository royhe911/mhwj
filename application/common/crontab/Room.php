<?php
namespace app\common\crontab;

use app\common\model\RoomUserModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 销毁房间定时任务类
 * @author 贺强
 * @time   2018-11-27 19:21:03
 */
class Room extends Command
{
    /**
     * 设置定时任务时间执行规则
     * @author 贺强
     * @time   2018-11-27 19:21:07
     */
    protected function configure()
    {
        $this->setName('room')->setDescription('here is the remark');
    }

    /**
     * 执行定时任务
     * @author 贺强
     * @time   2018-11-27 19:21:15
     */
    protected function execute(Input $input, Output $output)
    {
        $ru   = new RoomUserModel();
        $list = $ru->getList(['status' => 5, 'ready_time' => ['lt', time() - 300]], ['id', 'room_id']);
        if ($list) {
            $ids      = '0';
            $room_ids = '0';
            foreach ($list as $item) {
                $ids .= ",{$item['id']}";
                $room_ids .= ",{$item['room_id']}";
            }
            if ($ids !== '0') {
                $ru->modifyField('status', 4, ['id' => ['in', $ids]]);
            }
            if ($room_ids!=='0') {
                $r=new Room();
            }
        }
    }
}
