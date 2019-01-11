<?php
namespace app\common\model;

/**
 * FriendTopicModel类
 * @author 贺强
 * @time   2019-01-11 11:14:01
 */
class FriendTopicModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_friend_topic';
    }
}