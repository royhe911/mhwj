<?php
namespace app\common\model;

/**
 * GameModel类
 * @author 贺强
 * @time   2018-10-29 11:14:09
 */
class GameModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_game';
    }
}