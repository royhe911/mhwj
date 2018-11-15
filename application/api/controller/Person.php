<?php
namespace app\api\controller;

use app\common\model\PersonChatModel;
use app\common\model\PersonRoomModel;

/**
 * Person-控制器
 * @author 贺强
 * @time   2018-11-15 20:06:58
 */
class Person extends \think\Controller
{
    private $param = [];

    /**
     * 构造函数
     * @author 贺强
     * @time   2018-11-15 20:11:25
     */
    public function __construct()
    {
        $param = file_get_contents('php://input');
        $param = json_decode($param, true);
        if (empty($param['vericode'])) {
            echo json_encode(['status' => 300, 'info' => '非法参数', 'data' => null]);exit;
        }
        $vericode = $param['vericode'];
        unset($param['vericode']);
        $new_code = md5(config('MD5_PARAM'));
        if ($vericode !== $new_code) {
            echo json_encode(['status' => 100, 'info' => '非法参数', 'data' => null]);exit;
        }
        $this->param = $param;
    }

    /**
     * 进入私聊天房间
     * @author 贺强
     * @time   2018-11-15 20:10:15
     * @param  PersonRoomModel $pr PersonRoomModel 实例
     */
    public function come_in_room(PersonRoomModel $pr)
    {
        $param = $this->param;
        if (empty($param['order_id'])) {
            $msg = ['status' => 1, 'info' => '房间ID不能为空', 'data' => null];
        } elseif (empty($param['uid']) && empty($param['master_id'])) {
            $msg = ['status' => 2, 'info' => '玩家ID或者陪玩师ID不能同时为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $room    = $pr->getModel(['order_id' => $param['order_id']]);
        $chatlog = null;
        if ($room) {
            $res  = $pr->modify($param, ['order_id' => $param['order_id']]);
            $pc   = new PersonChatModel();
            $list = $pc->getList(['order_id' => $param['order_id']], ['avatar', 'content']);
        } else {
            $param['addtime'] = time();
            $res              = $pr->add($param);
        }
        if ($res !== false) {
            $msg = ['status' => 0, 'info' => '进入房间成功', 'data' => null];
        } else {
            $msg = ['status' => 4, 'info' => '进入房间失败', 'data' => $chatlog];
        }
        echo json_encode($msg);exit;
    }

    /**
     * 添加聊天记录
     * @author 贺强
     * @time   2018-11-15 21:32:37
     * @param  PersonChatModel $pc PersonChatModel 实例
     */
    public function add_chat(PersonChatModel $pc)
    {
        $param = $this->param;
        if (empty($param['order_id'])) {
            $msg = ['status' => 1, 'info' => '房间ID不能为空', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 3, 'info' => '说话用户ID不能为空', 'data' => null];
        } elseif (empty($param['content'])) {
            $msg = ['status' => 5, 'info' => '聊天内容不能为空', 'data' => null];
        } elseif (empty($param['avatar'])) {
            $msg = ['status' => 7, 'info' => '说话者头像不能为空', 'data' => null];
        }
        $param['addtime'] = time();
        $res              = $pc->add($param);
        if (!$res) {
            echo json_encode(['status' => 20, 'info' => '添加失败', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '添加成功', 'data' => null]);exit;
    }
}
