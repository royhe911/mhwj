<?php
namespace app\common\model;

/**
 * TUserGameModel类
 * @author 贺强
 * @time   2019-01-22 16:35:30
 */
class TUserGameModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 't_user_game';
    }
}