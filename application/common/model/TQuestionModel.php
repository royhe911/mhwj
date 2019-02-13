<?php
namespace app\common\model;

/**
 * TQuestionModel类
 * @author 贺强
 * @time   2019-02-13 14:54:14
 */
class TQuestionModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 't_question';
    }
}