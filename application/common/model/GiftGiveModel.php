<?php
namespace app\common\model;

/**
 * GiftGiveModel类
 * @author 贺强
 * @time   2019-01-10 12:26:19
 */
class GiftGiveModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_gift_give';
    }
}