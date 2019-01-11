<?php
namespace app\common\model;

/**
 * FriendModelModel类
 * @author 贺强
 * @time   2019-01-11 11:11:29
 */
class FriendModelModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_friend';
    }
}