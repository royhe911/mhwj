<?php
namespace app\common\model;

/**
 * TFeedbackModel类
 * @author 贺强
 * @time   2019-02-13 15:48:43
 */
class TFeedbackModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 't_feedback';
    }
}