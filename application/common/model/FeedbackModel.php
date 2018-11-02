<?php
namespace app\common\model;

/**
 * FeedbackModel类
 * @author 贺强
 * @time   2018-11-02 11:16:16
 */
class FeedbackModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_feedback';
    }
}