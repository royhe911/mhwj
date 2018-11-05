<?php
namespace app\common\model;

/**
 * RoomModel类
 * @author 贺强
 * @time   2018-11-05 16:30:09
 */
class RoomModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_room';
    }
}