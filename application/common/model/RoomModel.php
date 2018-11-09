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
            $sql  = "select id,in_count,count from m_room where id=$room_id for update";
            $data = Db::query($sql);
            $data = $data[0];
            if ($data['in_count'] < $data['count']) {
                $in_data = ['room_id' => $room_id, 'uid' => $uid, 'addtime' => time()];
                $res     = $ru->add($in_data);
                if (!$res) {
                    Db::rollback();
                    return 1;
                }
                $res = $this->modifyField('in_count', $data['in_count'] + 1, ['id' => $room_id]);
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
}
