<?php
namespace app\common\model;

/**
 * UserInviteModel类
 * @author 贺强
 * @time   2018-11-21 11:01:39
 */
class UserInviteModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_user_invite';
    }
}