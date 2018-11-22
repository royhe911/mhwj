<?php
namespace app\common\crontab;

use app\common\model\CouponModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 优惠卷定时任务类
 * @author 贺强
 * @time   2018-11-22 10:52:51
 */
class Coupon extends Command
{
    /**
     * 设置定时任务时间执行规则
     * @author 贺强
     * @time   2018-11-22 10:52:55
     */
    protected function configure()
    {
        $this->setName('coupon')->setDescription('here is the remark');
    }

    /**
     * 执行定时任务
     * @author 贺强
     * @time   2018-11-22 10:53:36
     */
    protected function execute(Input $input, Output $output)
    {
        $c    = new CouponModel();
        $list = $c->getList(['status' => 0, 'over_time' => ['lt', time()]], ['id']);
        if ($list) {
            $ids = '0';
            foreach ($list as $item) {
                $ids .= ",{$item['id']}";
            }
            if ($ids !== '0') {
                $c->modifyField('status', 3, ['id' => ['in', $ids]]);
            }
        }
    }
}
