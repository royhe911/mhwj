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
            $type = intval($type);
            if ($type === 1) {
                if ($data['count'] === 2) {
                    return 2;
                }
                $res = $this->decrement('count', ['id' => $room_id]);
            }
            if ($type === 2) {
                if ($data['count'] === 5) {
                    return 3;
                }
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
            // 进入房间锁定房间信息以免两个人同时进入
            $sql  = "select id,uid,price,num,in_count,count from m_room where id=$room_id for update";
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
                $in_data = ['room_id' => $room_id, 'uid' => $uid, 'addtime' => time(), 'price' => $data['price'], 'num' => $data['num'], 'total_money' => $data['price'] * $data['num']];
                // 添加进入房间信息
                $res = $ru->add($in_data);
                if (!$res) {
                    Db::rollback();
                    return 1;
                }
                // 进入房间成功后房间已进入的人数加 1
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
            $ru = new RoomUserModel();
            // 查询退出房间玩家信息
            $roomuser = $ru->getModel(['room_id' => $room_id, 'uid' => $uid]);
            if ($roomuser['status'] === 6) {
                $mo  = new MasterOrderModel();
                $res = $mo->decrement('complete_money', ['room_id' => $room_id], $roomuser['total_money']);
                if (!$res) {
                    Db::rollback();
                    return 3;
                }
                // 发起退款
                //
                //
                // 发起退款
            }
            // 删除退出房间的玩家
            $res = $ru->delByWhere(['room_id' => $room_id, 'uid' => $uid]);
            if (!$res) {
                Db::rollback();
                return 1;
            }
            // 一旦有人退出，其它人取消准备
            $ru->modifyField('status', 0, ['room_id' => $room_id]);
            // 退出后房间已进入的人数减 1
            $res = $this->decrement('in_count', ['id' => $room_id]);
            if (!$res) {
                Db::rollback();
                return 2;
            }
            $cu = new ChatUserModel();
            // 删除退出房间玩家的聊天信息
            $cu->delByWhere(['room_id' => $room_id, 'uid' => $uid]);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return 44;
        }
    }
}
