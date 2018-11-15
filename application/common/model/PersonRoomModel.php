<?php
namespace app\common\model;

/**
 * PersonRoomModel类
 * @author 贺强
 * @time   2018-11-15 20:08:59
 */
class PersonRoomModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_person_room';
    }
}