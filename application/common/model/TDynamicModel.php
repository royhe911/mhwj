<?php
namespace app\common\model;

/**
 * TUserDynamicModel类
 * @author 贺强
 * @time   2019-01-22 16:27:00
 */
class TDynamicModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 't_dynamic';
    }
}