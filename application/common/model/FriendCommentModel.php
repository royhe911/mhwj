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
            $type = $param['type'];
            if (intval($type) === 1) {
                $res = $this->increment('pl_count', ['id' => $param['obj_id']]);
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
