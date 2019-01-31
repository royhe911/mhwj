<?php
namespace app\common\model;

use think\Db;

/**
 * TUserDynamicCommentModel类
 * @author 贺强
 * @time   2019-01-22 16:34:53
 */
class TDynamicCommentModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 't_dynamic_comment';
    }

    /**
     * 评论
     * @author 贺强
     * @time   2019-01-22 20:17:13
     * @param  array $param 评论参数
     */
    public function do_comment($param)
    {
        Db::startTrans();
        try {
            $u      = new TUserModel();
            $uid    = $param['uid'];
            $did    = $param['did'];
            $type   = intval($param['type']);
            $d      = new TDynamicModel();
            $dy     = $d->getModel(['id' => $did], ['uid']);
            $obj_id = $param['obj_id'];
            $ddat   = ['did' => $did, 'obj_id' => $obj_id, 'type' => 1, 'content' => $param['content'], 'uid' => $dy['uid'], 'tid' => $uid, 'addtime' => time()];
            if ($type === 2) {
                $obj  = $this->getModel(['id' => $obj_id], ['uid']);
                $cdat = ['did' => $did, 'obj_id' => $obj_id, 'type' => 1, 'content' => $param['content'], 'uid' => $obj['uid'], 'tid' => $uid, 'addtime' => time()];
            }
            $user = $u->getModel(['id' => $uid], ['nickname', 'avatar', 'sex', 'status']);
            if (!empty($user)) {
                if ($user['status'] === 44) {
                    Db::rollback();
                    return 43;
                }
                // 获取评论者的昵称、头像、性别
                $param['nickname'] = $user['nickname'];
                $param['avatar']   = $user['avatar'];
                $param['sex']      = $user['sex'];
                $ddat['nickname']  = $user['nickname'];
                $ddat['avatar']    = $user['avatar'];
                if (!empty($cdat)) {
                    $cdat['nickname'] = $user['nickname'];
                    $cdat['avatar']   = $user['avatar'];
                }
            }
            $res = $this->add($param);
            if (!$res) {
                Db::rollback();
                return 10;
            }
            // 添加评论/回复通知
            $n = new TNoticeModel();
            if (!empty($cdat)) {
                $res = $n->addArr([$ddat, $cdat]);
            } else {
                $res = $n->add($ddat);
            }
            if (!$res) {
                Db::rollback();
                return 30;
            }
            // 被评论或回复的动态评论数加 1
            $d   = new TDynamicModel();
            $res = $d->increment('pl_count', ['id' => $did]);
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
     * @time   2019-01-23 16:15:36
     * @param  integer $id 要删除的评论ID
     */
    public function del_comment($id)
    {
        Db::startTrans();
        try {
            // 查询数据是否存在
            $comm = $this->getModel(['id' => $id], true, '', true);
            if (!$comm) {
                Db::rollback();
                return 10;
            }
            // 删除评论下的回复
            $res = $this->delByWhere(['cid' => $id]);
            if ($res === false) {
                Db::rollback();
                return 40;
            }
            // 删除评论
            $res = $this->delById($id);
            if ($res === false) {
                Db::rollback();
                return 20;
            }
            $d = new TDynamicModel();
            // 相应动态的评论数减 1
            $res = $d->decrement('pl_count', ['id' => $comm['did']]);
            if ($res === false) {
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
