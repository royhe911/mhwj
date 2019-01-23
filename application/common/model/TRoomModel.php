<?php
namespace app\common\model;

/**
 * TRoomModel类
 * @author 贺强
 * @time   2019-01-23 11:59:15
 */
class TRoomModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 't_room';
    }
}