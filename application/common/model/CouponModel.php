<?php
namespace app\common\model;

/**
 * CouponModel类
 * @author 贺强
 * @time   2018-11-14 12:11:13
 */
class CouponModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_coupon';
    }
}