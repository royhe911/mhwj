<?php
namespace app\common\model;

/**
 * GameConfigModel类
 * @author 贺强
 * @time   2018-10-31 15:47:14
 */
class GameConfigModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_game_config';
    }
}