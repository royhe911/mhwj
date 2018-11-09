<?php
namespace app\common\model;

/**
 * RoomUserModel类
 * @author 贺强
 * @time   2018-11-09 09:34:19
 */
class RoomUserModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_room_user';
    }
}