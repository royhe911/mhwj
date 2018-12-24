<?php
namespace app\common\model;

/**
 * InroomLogModel类
 * @author 贺强
 * @time   2018-12-21 17:27:41
 */
class InroomLogModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_inroom_log';
    }
}