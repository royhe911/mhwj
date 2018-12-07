<?php
namespace app\api\controller;

use app\common\model\PersonChatModel;
use app\common\model\PersonRoomModel;
use app\common\model\UserModel;

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
            $res     = $pr->modify($param, ['order_id' => $param['order_id']]);
            $pc      = new PersonChatModel();
            $chatlog = $pc->getList(['order_id' => $param['order_id']], ['avatar', 'content', 'author_type']);
        } else {
            $param['addtime'] = time();
            $res              = $pr->add($param);
        }
        $members = null;
        $person  = $pr->getModel(['order_id' => $param['order_id']]);
        $u       = new UserModel();
        $users   = $u->getList(['id' => ['in', "{$person['uid']},{$person['master_id']}"]], ['id', 'nickname', 'avatar', 'qq', 'wx']);
        foreach ($users as $user) {
            if ($user['id'] === $person['master_id']) {
                $members['master'] = $user;
            }
            if ($user['id'] === $person['uid']) {
                $members['users'] = $user;
            }
        }
        if (empty($members['master'])) {
            $members['master'] = null;
        }
        if (empty($members['users'])) {
            $members['users'] = null;
        }
        if ($res !== false) {
            $msg = ['status' => 0, 'info' => '进入房间成功', 'data' => ['members' => $members, 'chatlog' => $chatlog]];
        } else {
            $msg = ['status' => 4, 'info' => '进入房间失败', 'data' => null];
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
        } elseif (empty($param['nickname'])) {
            $msg = ['status' => 2, 'info' => '说话者昵称不能为空', 'data' => null];
        } elseif (empty($param['author_type'])) {
            $msg = ['status' => 4, 'info' => '说话者身份不能为空', 'data' => null];
        } elseif (empty($param['content'])) {
            $msg = ['status' => 5, 'info' => '聊天内容不能为空', 'data' => null];
        } elseif (empty($param['avatar'])) {
            $msg = ['status' => 7, 'info' => '说话者头像不能为空', 'data' => null];
        }
        $param['addtime'] = time();
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $res = $pc->add($param);
        if (!$res) {
            echo json_encode(['status' => 20, 'info' => '添加失败', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '添加成功', 'data' => null]);exit;
    }

    /**
     * 获取私聊房间
     * @author 贺强
     * @time   2018-11-16 14:14:24
     * @param  PersonRoomModel $pr PersonRoomModel 实例
     */
    public function get_person_room(PersonRoomModel $pr)
    {
        $param = $this->param;
        if (empty($param['id'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $field = 'master_id';
        if (!empty($param['type']) && intval($param['type']) === 1) {
            $field = 'uid';
        }
        $list = $pr->getList(["$field" => $param['id']], ['order_id']);
        if (!$list) {
            echo json_encode(['status' => 4, 'info' => '暂无私聊', 'data' => null]);exit;
        }
        $order_ids = array_column($list, 'order_id');
        // 查询私聊最新记录
        $pc      = new PersonChatModel();
        $sql     = 'select * from (select order_id,nickname,avatar,content,addtime from m_person_chat order by addtime desc) t group by t.order_id';
        $chatlog = $pc->query($sql);
        foreach ($chatlog as &$chat) {
            if (!empty($chat['addtime'])) {
                $chat['addtime'] = date('Y-m-d H:i:s', $chat['addtime']);
            }
        }
        $chatlog = array_column($chatlog, null, 'order_id');
        $u       = new UserModel();
        foreach ($list as &$item) {
            if (!empty($chatlog[$item['order_id']])) {
                $item = $chatlog[$item['order_id']];
            } else {
                $user = $u->getModel(['id' => $param['id']], ['nickname', 'avatar']);
                // 给聊天内容和时间赋空值
                $user['order_id'] = $item['order_id'];
                $user['content']  = '';
                $user['addtime']  = '';
                $item             = $user;
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }
}
