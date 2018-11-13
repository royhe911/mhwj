<?php
namespace app\common\model;

/**
 * ChatUserModel类
 * @author 贺强
 * @time   2018-11-13 11:20:17
 */
class ChatUserModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_chat_user';
    }
}