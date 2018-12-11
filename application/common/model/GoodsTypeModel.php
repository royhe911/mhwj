<?php
namespace app\common\model;

/**
 * GoodsTypeModel类
 * @author 贺强
 * @time   2018-12-11 09:22:15
 */
class GoodsTypeModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_goods_type';
    }
}