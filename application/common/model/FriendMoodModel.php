<?php
namespace app\common\model;

/**
 * FriendMoodModel类
 * @author 贺强
 * @time   2019-01-11 11:13:09
 */
class FriendMoodModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_friend_mood';
    }
}