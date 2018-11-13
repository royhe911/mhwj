<?php
namespace app\common\model;

use think\Db;

/**
 * RoomModel类
 * @author 贺强
 * @time   2018-11-05 16:30:09
 */
class RoomModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_room';
    }

    /**
     * 关闭/打开位置
     * @author 贺强
     * @time   2018-11-09 17:14:44
     * @param  int  $room_id 房间ID
     * @param  int  $type    操作类型，1关闭 2打开
     * @return boll          返回是否关闭成功
     */
    public function set_seat($room_id, $type = 1)
    {
        Db::startTrans();
        try {
            $sql  = "select id,in_count,count from m_room where id=$room_id for update";
            $data = Db::query($sql);
            if (!$data) {
                Db::rollback();
                return 4;
            }
            $data = $data[0];
            if ($type === 1 && $data['count'] === 2) {
                return 2;
            } elseif ($type === 2 && $data['count'] === 5) {
                return 3;
            }
            if ($type === 1) {
                $res = $this->decrement('count', ['id' => $room_id]);
            } else {
                $res = $this->increment('count', ['id' => $room_id]);
            }
            if (!$res) {
                Db::rollback();
                return 1;
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return 44;
        }
    }

    /**
     * 进入房间
     * @author 贺强
     * @time   2018-11-09 10:25:04
     * @param  int  $room_id 进入的房间ID
     * @param  int  $uid     进入用户ID
     * @return bool          是否进入成功
     */
    public function in_room($room_id, $uid)
    {
        $ru       = new RoomUserModel();
        $roomuser = $ru->getCount(['room_id' => $room_id, 'uid' => $uid]);
        if ($roomuser) {
            return true;
        }
        Db::startTrans();
        try {
            $sql  = "select id,uid,in_count,count from m_room where id=$room_id for update";
            $data = Db::query($sql);
            if (!$data) {
                Db::rollback();
                return 4;
            }
            $data = $data[0];
            if ($uid === $data['uid']) {
                return true;
            }
            if ($data['in_count'] < $data['count']) {
                $in_data = ['room_id' => $room_id, 'uid' => $uid, 'addtime' => time()];
                $res     = $ru->add($in_data);
                if (!$res) {
                    Db::rollback();
                    return 1;
                }
                $res = $this->modifyField('in_count', $data['in_count'] + 1, ['id' => $room_id]);
                // var_dump($res);exit;
                if (!$res) {
                    Db::rollback();
                    return 2;
                }
                Db::commit();
                return true;
            }
            Db::rollback();
            return 3;
        } catch (\Exception $e) {
            Db::rollback();
            return 44;
        }
    }

    /**
     * 退出房间
     * @author 贺强
     * @time   2018-11-09 16:51:36
     * @param  int  $room_id 房间ID
     * @param  int  $uid     用户ID
     * @return bool          返回是否退出成功
     */
    public function quit_room($room_id, $uid)
    {
        Db::startTrans();
        try {
            $ru  = new RoomUserModel();
            $res = $ru->delByWhere(['room_id' => $room_id, 'uid' => $uid]);
            if (!$res) {
                Db::rollback();
                return 1;
            }
            $res = $this->decrement('in_count', ['id' => $room_id]);
            if (!$res) {
                Db::rollback();
                return 2;
            }
            $cu = new ChatUserModel();
            $cu->delByWhere(['room_id' => $room_id, 'uid' => $uid]);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return 44;
        }
    }
}
