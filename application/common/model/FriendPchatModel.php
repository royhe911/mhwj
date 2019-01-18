<?php
namespace app\common\model;

/**
 * FriendPchatModel类
 * @author 贺强
 * @time   2019-01-18 16:17:35
 */
class FriendPchatModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_friend_pchat';
    }
}