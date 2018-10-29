<?php
namespace app\common\model;

/**
 * MasterOrderModel类
 * @author 贺强
 * @time   2018-10-26 16:24:09
 */
class MasterOrderModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_master_order';
    }
}