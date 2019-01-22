<?php
namespace app\common\model;

/**
 * TTopicModel类
 * @author 贺强
 * @time   2019-01-22 20:50:13
 */
class TTopicModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 't_topic';
    }
}