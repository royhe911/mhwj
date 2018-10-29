<?php
namespace app\common\model;

/**
 * UserAttrModel类
 * @author 贺强
 * @time   2018-10-26 16:15:39
 */
class UserAttrModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_user_attr';
    }
}