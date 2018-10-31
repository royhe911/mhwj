<?php
namespace app\common\model;

use think\Db;

/**
 * UserModel类
 * @author 贺强
 * @time   2018-10-26 16:14:46
 */
class UserModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_user';
    }
}
