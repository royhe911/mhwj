<?php
namespace app\common\model;

/**
 * FriendChatModel类
 * @author 贺强
 * @time   2019-01-17 09:47:00
 */
class FriendChatModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_friend_chat';
    }
}