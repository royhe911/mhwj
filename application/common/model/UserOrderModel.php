<?php
namespace app\common\model;

/**
 * UserOrderModel类
 * @author 贺强
 * @time   2018-10-26 16:23:06
 */
class UserOrderModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_user_order';
    }
}