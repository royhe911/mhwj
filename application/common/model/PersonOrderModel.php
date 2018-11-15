<?php
namespace app\common\model;

/**
 * PersonalOrderModel类
 * @author 贺强
 * @time   2018-11-15 15:19:18
 */
class PersonOrderModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_personal_order';
    }
}