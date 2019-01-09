<?php
namespace app\common\model;

/**
 * RoomNoticeModel类
 * @author 贺强
 * @time   2019-01-09 14:13:01
 */
class RoomNoticeModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_room_notice';
    }
}