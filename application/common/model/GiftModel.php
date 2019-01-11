<?php
namespace app\common\model;

/**
 * GiftModel类
 * @author 贺强
 * @time   2019-01-10 12:25:38
 */
class GiftModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_gift';
    }
}