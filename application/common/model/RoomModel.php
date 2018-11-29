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
            $where = "id=$room_id";
            $sql   = "select id,price,num,total_money,in_count,count,master_count,status from m_room where {$where} for update";
            $data  = Db::query($sql);
            if (!$data) {
                Db::rollback();
                return 4;
            }
            $data = $data[0];
            if ($data['status'] === 10 || $data['status'] === 8 || $data['status'] === 5) {
                return 10;
            }
            $type = intval($type);
            $mo   = new MasterOrderModel();
            if ($type === 1) {
                $total_money = $data['total_money'] - $data['num'] * $data['price'];
                if ($data['count'] === 1) {
                    return 2;
                }
                $dida = ['count' => $data['count'] - 1, 'total_money' => $total_money];
            }
            if ($type === 2) {
                $total_money = $data['total_money'] + $data['num'] * $data['price'];
                if ($data['count'] + $data['master_count'] === 5) {
                    return 3;
                }
                $ru = new RoomUserModel();
                $ru->modifyField('status', 0, ['room_id' => $room_id]);
                $dida = ['count' => $data['count'] + 1, 'total_money' => $total_money];
            }
            $mo->modifyField('order_money', $total_money, ['room_id' => $room_id]);
            $res = $this->modify($dida, $where);
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
     * @param  array $param 进入的房间用户数据
     * @return bool         是否进入成功
     */
    public function in_room($param)
    {
        Db::startTrans();
        try {
            // 进入房间锁定房间信息以免两个人同时进入
            $sql  = "select id,uid,price,num,in_count,count,in_master_count,master_count,status from m_room where id={$param['room_id']} for update";
            $data = Db::query($sql);
            if (!$data) {
                Db::rollback();
                return 4;
            }
            $data = $data[0];
            if (intval($param['uid']) === $data['uid']) {
                return true;
            }
            if ($data['status'] === 10) {
                return 11;
            }
            if ($data['status'] === 9) {
                return 12;
            }
            if (intval($param['type']) === 1) {
                $ru    = new RoomUserModel();
                $count = $ru->getCount(['room_id' => $data['id'], 'uid' => $param['uid']]);
                if ($count) {
                    return true;
                }
                if ($data['in_count'] < $data['count']) {
                    $in_data = ['room_id' => $data['id'], 'uid' => $param['uid'], 'addtime' => time(), 'price' => $data['price'], 'num' => $data['num'], 'total_money' => $data['price'] * $data['num']];
                    // 添加进入房间信息
                    $res = $ru->add($in_data);
                    if (!$res) {
                        Db::rollback();
                        return 1;
                    }
                    // 进入房间成功后房间已进入的人数加 1
                    $res = $this->modifyField('in_count', $data['in_count'] + 1, ['id' => $data['id']]);
                    // var_dump($res);exit;
                    if (!$res) {
                        Db::rollback();
                        return 2;
                    }
                    Db::commit();
                    return true;
                }
            } elseif (intval($param['type']) === 2) {
                $mu    = new RoomMasterModel();
                $count = $mu->getCount(['room_id' => $data['id'], 'uid' => $param['uid']]);
                if ($count) {
                    return true;
                }
                if ($data['in_master_count'] < $data['master_count']) {
                    $in_data = ['room_id' => $data['id'], 'uid' => $param['uid'], 'addtime' => time()];
                    $res     = $mu->add($in_data);
                    if (!$res) {
                        Db::rollback();
                        return 2;
                    }
                    // 进入房间成功后房间已进入的人数加 1
                    $res = $this->modifyField('in_master_count', $data['in_master_count'] + 1, ['id' => $data['id']]);
                    // var_dump($res);exit;
                    if (!$res) {
                        Db::rollback();
                        return 2;
                    }
                    Db::commit();
                    return true;
                }
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
            $cu   = new ChatUserModel();
            $ru   = new RoomUserModel();
            $room = $this->getModel(['id' => $room_id]);
            if ($room['count'] === 1) {
                $this->modify(['status' => 1, 'in_count' => 0], ['id' => $room_id]);
                $mo = new MasterOrderModel();
                $mo->modifyField('status', 0, ['room_id' => $room_id]);
            } elseif ($room['status'] === 5) {
                $ru->modifyField('status', 4, ['room_id' => $room_id]);
                $this->modifyField('status', 9, ['id' => $room_id]);
                $cu->delByWhere(['room_id' => $room_id]);
                $c = new ChatModel();
                $c->delByWhere(['room_id' => $room_id]);
            } else {
                // 一旦有人退出，其它人取消准备
                $ru->modifyField('status', 0, ['room_id' => $room_id, 'status' => ['<>', 6]]);
                // 退出后房间已进入的人数减 1
                $res = $this->decrement('in_count', ['id' => $room_id]);
                if (!$res) {
                    Db::rollback();
                    return 2;
                }
            }
            // 删除退出房间的玩家
            $res = $ru->delByWhere(['room_id' => $room_id, 'uid' => $uid]);
            if (!$res) {
                Db::rollback();
                return 1;
            }
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
