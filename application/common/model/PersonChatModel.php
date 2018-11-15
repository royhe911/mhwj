<?php
namespace app\common\model;

/**
 * PersonChatModel类
 * @author 贺强
 * @time   2018-11-15 21:31:42
 */
class PersonChatModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_person_chat';
    }
}