<?php
namespace app\common\model;

/**
 * MessageModel类
 * @author 贺强
 * @time   2018-11-02 14:09:00
 */
class MessageModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_message';
    }
}