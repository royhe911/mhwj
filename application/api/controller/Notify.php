<?php
namespace app\api\controller;

/**
 * Notify-控制器
 * @author 贺强
 * @time   2018-11-27 12:26:02
 */
class Notify extends \think\Controller
{

    /**
     * 微信支付回调
     * @author 贺强
     * @time   2018-11-27 11:26:39
     */
    public function pay_notify()
    {
        $param = $this->request->get();
        if (empty($param)) {
            $param = $this->request->post();
        }
        file_put_contents('/application/api/controller/result.log', json_encode($param));
    }

}
