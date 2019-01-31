<?php
namespace app\common\model;

/**
 * TNoticeModel类
 * @author 贺强
 * @time   2019-01-31 10:37:48
 */
class TNoticeModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 't_notice';
    }
}