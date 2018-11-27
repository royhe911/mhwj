<?php
namespace app\common\model;

class LogModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_log';
    }

    const TYPE_REFUND = 1; // 用户退款

    /**
     * 写操作日志
     * @author  贺强
     * @time    2018-10-25 14:03:35
     * @param   array      $data 要写入的数据
     */
    public function addLog($data)
    {
        $data['addtime'] = time();
        $this->add($data);
    }
}
