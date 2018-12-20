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
            $master   = $r->getModel(['id' => $data['room_id']], 'uid,id room_id');
            $ids      = [$master];
            $rm       = new RoomMasterModel();
            $mid_list = $rm->getList(['room_id' => $data['room_id'], 'is_delete' => 0], ['uid', 'room_id']);
            $ids      = array_merge($ids, $mid_list);
            $ru       = new RoomUserModel();
            $uid_list = $ru->getList(['room_id' => $data['room_id']], 'uid,room_id');
            $ids      = array_merge($ids, $uid_list);
            foreach ($ids as &$id) {
                $id['chat_id'] = $res;
                $id['addtime'] = time();
            }
            $cu  = new ChatUserModel();
            $res = $cu->addArr($ids);
            if (!$res) {
                Db::rollback();
                return 20;
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return 44;
        }
    }
}
