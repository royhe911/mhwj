<?php
namespace app\common\model;

class MenuModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_menu';
    }
}