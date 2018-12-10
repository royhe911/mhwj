<?php
namespace app\common\model;

/**
 * GoodsModel类
 * @author 贺强
 * @time   2018-12-10 16:48:14
 */
class GoodsModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_goods';
    }
}