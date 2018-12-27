<?php
namespace app\common\model;

/**
 * PrizeUserModel类
 * @author 贺强
 * @time   2018-12-26 16:10:11
 */
class PrizeUserModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_prize_user';
    }
}