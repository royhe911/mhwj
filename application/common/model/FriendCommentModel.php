<?php
namespace app\common\model;

use think\Db;

/**
 * FriendCommentModel类
 * @author 贺强
 * @time   2019-01-11 11:12:17
 */
class FriendCommentModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_friend_comment';
    }

    /**
     * 评论操作
     * @author 贺强
     * @time   2019-01-11 16:58:41
     * @param  array $param 评论参数
     */
    public function do_comment($param)
    {
        Db::startTrans();
        try {
            $res = $this->add($param);
            if (!$res) {
                Db::rollback();
                return 10;
            }
            $fm  = new FriendMoodModel();
            $res = $fm->increment('pl_count', ['id' => $param['mood_id']]);
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

    /**
     * 删除评论
     * @author 贺强
     * @time   2019-01-15 14:42:45
     * @param  integer $id 要删除的评论ID
     */
    public function del_comment($id)
    {
        Db::startTrans();
        try {
            // 查询数据是否存在
            $comm = $this->getModel(['id' => $id]);
            if (!$comm) {
                Db::rollback();
                return 10;
            }
            // 删除评论
            $res = $this->delById($id);
            if (!$res) {
                Db::rollback();
                return 20;
            }
            $fm = new FriendMoodModel();
            // 相应心情的评论数减 1
            $res = $fm->decrement('pl_count', ['id' => $comm['mood_id']]);
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
