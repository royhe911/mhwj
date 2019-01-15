<?php
namespace app\common\model;

use think\Db;

/**
 * FriendMoodModel类
 * @author 贺强
 * @time   2019-01-11 11:13:09
 */
class FriendMoodModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_friend_mood';
    }

    /**
     * 删除心情
     * @author 贺强
     * @time   2019-01-15 14:28:09
     * @param  integer $id 要删除的心情ID
     */
    public function del_mood($id)
    {
        Db::startTrans();
        try {
            // 查询数据是否存在
            $mood = $this->getModel(['id' => $id]);
            if (!$mood) {
                Db::rollback();
                return 10;
            }
            // 删除数据
            $res = $this->delById($id);
            if (!$res) {
                Db::rollback();
                return 20;
            }
            // 删除对应的评论
            $fc  = new FriendCommentModel();
            $res = $fc->delByWhere(['mood_id' => $id]);
            if ($res === false) {
                Db::rollback();
                return 30;
            }
            // 相应话题的发布量减 1
            if (!empty($mood['topic'])) {
                $ft  = new FriendTopicModel();
                $res = $ft->decrement('count', ['id' => ['in', $mood['topic']]]);
                if (!$res) {
                    Db::rollback();
                    return 40;
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
