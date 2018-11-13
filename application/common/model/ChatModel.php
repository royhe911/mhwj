<?php
namespace app\common\model;

use think\Db;

/**
 * ChatModel类
 * @author 贺强
 * @time   2018-11-13 11:18:28
 */
class ChatModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_chat';
    }

    /**
     * 添加聊天记录
     * @author 贺强
     * @time   2018-11-13 11:38:50
     */
    public function add_chat($data)
    {
        Db::startTrans();
        try {
            $res = $this->add($data);
            if (!$res) {
                Db::rollback();
                return 10;
            }
            $r        = new RoomModel();
            $master   = $r->getModel(['id' => $data['room_id']], 'id room_id,uid');
            $ru       = new RoomUserModel();
            $uid_list = $ru->getList(['room_id' => $data['room_id']], 'uid,room_id');
            $uid_list = array_merge($uid_list, [$master]);
            if (!empty($uid_list)) {
                foreach ($uid_list as &$uid) {
                    $uid['chat_id'] = $res;
                }
                $cu  = new ChatUserModel();
                $res = $cu->addArr($uid_list);
                if (!$res) {
                    Db::rollback();
                    return 20;
                }
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return 44;
        }
    }
}
