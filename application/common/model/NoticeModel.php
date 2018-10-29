<?php
namespace app\common\model;

/**
 * NoticeModel类
 * @author 贺强
 * @time   2018-10-26 11:31:46
 */
class NoticeModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_notice';
    }
}