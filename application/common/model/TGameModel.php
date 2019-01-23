<?php
namespace app\common\model;

/**
 * TGameModel类
 * @author 贺强
 * @time   2019-01-23 17:33:37
 */
class TGameModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 't_game';
    }
}