<?php
namespace app\common\model;

/**
 * FriendCommentModel类
 * @author 贺强
 * @time   2019-01-11 11:12:17
 */
class FriendCommentModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_friend_comment';
    }
}