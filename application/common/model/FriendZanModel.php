<?php
namespace app\common\model;

use think\Db;

/**
 * FriendZanModel类
 * @author 贺强
 * @time   2019-01-11 14:36:06
 */
class FriendZanModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_friend_zan';
    }

    /**
     * 点赞操作
     * @author 贺强
     * @time   2019-01-11 14:55:42
     * @param  array $param 点赞参数
     */
    public function do_zan($param)
    {
        Db::startTrans();
        try {
            $id    = $param['obj_id'];
            $type  = $param['type'];
            $model = new FriendMoodModel();
            if (intval($type) === 2) {
                $model = new FriendCommentModel();
            }
            $data = $model->getModel(['id' => $id], ['id'], '', true);
            if (!$data) {
                Db::rollback();
                return 10;
            }
            $where = ['obj_id' => $id, 'type' => $type, 'uid' => $param['uid']];
            $count = $this->getCount($where);
            if (!$count) {
                $res = $model->increment('zan_count', ['id' => $id]);
            } else {
                $res = $model->decrement('zan_count', ['id' => $id]);
            }
            if (!$res) {
                Db::rollback();
                return 20;
            }
            if (!$count) {
                $res = $this->add($param);
            } else {
                $res = $this->delByWhere($where);
            }
            if (!$res) {
                Db::rollback();
                return 30;
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return 44;
        }
    }
}
