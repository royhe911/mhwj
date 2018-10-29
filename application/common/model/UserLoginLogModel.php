<?php
namespace app\common\model;

/**
 * UserLoginLogModel类
 * @author 贺强
 * @time   2018-10-26 16:17:50
 */
class UserLoginLogModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_user_login_log';
    }
}