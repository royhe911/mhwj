<?php
namespace app\common\model;

class AdminModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_admin';
    }
}