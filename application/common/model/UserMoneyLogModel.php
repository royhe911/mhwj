<?php
namespace app\common\model;

/**
 * UserMoneyLogModel类
 * @author 贺强
 * @time   2018-10-26 16:19:06
 */
class UserMoneyLogModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_user_money_log';
    }
}