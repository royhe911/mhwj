<?php
namespace app\common\model;

/**
 * RoomMasterModel类
 * @author 贺强
 * @time   2018-11-23 12:07:18
 */
class RoomMasterModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_room_master';
    }
}