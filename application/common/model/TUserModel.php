<?php
namespace app\common\model;

/**
 * TUserModel类
 * @author 贺强
 * @time   2019-01-22 16:34:10
 */
class TUserModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 't_user';
    }
}