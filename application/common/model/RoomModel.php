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
            $sql   = "select id,price,`type`,num,total_money,in_count,count,master_count,status from m_room where {$where} for update";
            $data  = Db::query($sql);
            if (!$data) {
                Db::rollback();
                return 4;
            }
            $data = $data[0];
            if ($data['status'] === 10 || $data['status'] === 8 || $data['status'] === 5 || $data['status'] === 6) {
                return 10;
            }
            if ($data['type'] === 2) {
                return 10;
            }
            $type = intval($type);
            $mo   = new MasterOrderModel();
            if ($type === 1) {
                $total_money = $data['total_money'] - $data['num'] * $data['price'];
                if ($data['count'] === 1) {
                    return 2;
                }
                $dida  = ['count' => $data['count'] - 1, 'total_money' => $total_money];
                $ru    = new RoomUserModel();
                $count = $ru->getCount(['room_id' => $room_id, 'status' => 6]);
                if ($dida['count'] === $count) {
                    $dida['status'] = 6;
                }
            }
            if ($type === 2) {
                $total_money = $data['total_money'] + $data['num'] * $data['price'];
                if ($data['count'] + $data['master_count'] === 5) {
                    return 3;
                }
                $ru = new RoomUserModel();
                $ru->modifyField('status', 0, ['room_id' => $room_id]);
                $dida = ['count' => $data['count'] + 1, 'total_money' => $total_money, 'status' => 0];
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
    public function in_room($param, $is_new = null)
    {
        Db::startTrans();
        try {
            $room_id = $param['room_id'];
            // 进入房间锁定房间信息以免两个人同时进入
            $sql  = "select id,uid,price,type,num,in_count,count,in_master_count,master_count,status from m_room where id={$room_id} for update";
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
            $type = intval($param['type']);
            $uid  = $param['uid'];
            $il   = new InroomLogModel();
            $il->add(['room_id' => $room_id, 'uid' => $uid, 'type' => $type, 'addtime' => time()]);
            if ($type === 1) {
                $ru    = new RoomUserModel();
                $count = $ru->getCount(['room_id' => $data['id'], 'uid' => $uid]);
                if ($count) {
                    return true;
                }
                if ($data['in_count'] < $data['count']) {
                    if ($data['type'] === 1) {
                        $gc    = new GameConfigModel();
                        $gconf = $gc->getModel(['para_str' => $param['para_str']], ['price']);
                        $price = $gconf['price'];
                    } else {
                        $price = $data['price'];
                    }
                    $in_data = ['room_id' => $data['id'], 'uid' => $uid, 'addtime' => time(), 'price' => $price, 'num' => $data['num'], 'total_money' => $price * $data['num']];
                    if ($is_new === 1) {
                        $in_data['status'] = 5;
                    }
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
            } elseif ($type === 2) {
                $rm    = new RoomMasterModel();
                $count = $rm->getCount(['room_id' => $data['id'], 'uid' => $uid]);
                if ($count) {
                    $rm->modifyField('is_delete', 0, ['room_id' => $data['id'], 'uid' => $uid]);
                    return true;
                }
                if ($data['in_master_count'] < $data['master_count']) {
                    $ua    = new UserAttrModel();
                    $count = $ua->getCount(['uid' => $uid, 'play_type' => 1]);
                    if (!$count) {
                        return 5;
                    }
                    $in_data = ['room_id' => $data['id'], 'uid' => $uid, 'addtime' => time()];
                    $res     = $rm->add($in_data);
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
     * @param  int  $is_yule 是否是娱乐房间
     * @return bool          返回是否退出成功
     */
    public function quit_room($room_id, $uid, $is_yule = 1)
    {
        Db::startTrans();
        try {
            $cu   = new ChatUserModel();
            $ru   = new RoomUserModel();
            $room = $this->getModel(['id' => $room_id]);
            if ($is_yule === 1) {
                $mo = new MasterOrderModel();
                $mo->modifyField('status', 0, ['room_id' => $room_id]);
            }
            // 一旦有人退出，其它人取消准备
            $ru->modifyField('status', 0, ['room_id' => $room_id, 'status' => ['<>', 6]]);
            // 退出后房间已进入的人数减 1
            $res = $this->decrement('in_count', ['id' => $room_id]);
            if (!$res) {
                Db::rollback();
                return 2;
            }
            $res = $this->modifyField('is_tip', 0, ['id' => $room_id]);
            if ($res === false) {
                Db::rollback();
                return 3;
            }
            // 删除退出房间的玩家订单
            $uo = new UserOrderModel();
            $uo->delByWhere(['uid' => $uid]);
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
