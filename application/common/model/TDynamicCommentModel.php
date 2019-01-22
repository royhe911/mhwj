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
            $u    = new TUserModel();
            $user = $u->getModel(['id' => $param['uid']], ['nickname', 'avatar', 'sex']);
            if (!empty($user)) {
                // 获取评论者的昵称、头像、性别
                $param['nickname'] = $user['nickname'];
                $param['avatar']   = $user['avatar'];
                $param['sex']      = $user['sex'];
            }
            $res = $this->add($param);
            if (!$res) {
                Db::rollback();
                return 10;
            }
            // 被评论或回复的动态评论数加 1
            $fm  = new TDynamicModel();
            $res = $fm->increment('pl_count', ['id' => $param['did']]);
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
}
