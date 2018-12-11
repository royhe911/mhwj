<?php
namespace app\common\model;

use think\Db;

/**
 * GoodsTaskModel类
 * @author 贺强
 * @time   2018-12-11 09:20:19
 */
class GoodsModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_goods';
    }
}