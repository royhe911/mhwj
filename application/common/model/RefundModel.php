<?php
namespace app\common\model;

/**
 * RefundModel类
 * @author 贺强
 * @time   2018-11-27 16:25:50
 */
class RefundModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_refund';
    }
}