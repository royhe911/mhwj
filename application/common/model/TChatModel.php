<?php
namespace app\common\model;

/**
 * TChatModel类
 * @author 贺强
 * @time   2019-01-23 11:42:22
 */
class TChatModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 't_chat';
    }
}