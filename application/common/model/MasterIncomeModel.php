<?php
namespace app\common\model;

/**
 * MasterIncomeModel类
 * @author 贺强
 * @time   2018-12-17 09:36:06
 */
class MasterIncomeModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_master_income';
    }
}