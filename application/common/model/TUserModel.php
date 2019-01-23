<?php
namespace app\common\model;

/**
 * TUserModel类
 * @author 贺强
 * @time   2019-01-22 16:34:10
 */
class TUserModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 't_user';
    }

    public function syncinfo($param)
    {
        $data = [];
        $fda1 = [];
        $fda2 = [];
        if (!empty($param['nickname'])) {
            $data['nickname']  = $param['nickname'];
            $fda1['nickname1'] = $param['nickname'];
            $fda2['nickname2'] = $param['nickname'];
        }
        if (!empty($param['avatar'])) {
            $data['avatar']  = $param['avatar'];
            $fda1['avatar1'] = $param['avatar'];
            $fda2['avatar2'] = $param['avatar'];
        }
        if (!empty($param['sex'])) {
            $data['sex']  = $param['sex'];
            $fda1['sex1'] = $param['sex'];
            $fda2['sex2'] = $param['sex'];
        }
        $id = $param['id'];
        Db::startTrans();
        try {
            $res = $this->modify($param, ['id' => $id]);
            if ($res === false) {
                Db::rollback();
                return 10;
            }
            if ($data) {
                // 修改动态表用户的昵称、头像、性别
                $d   = new TDynamicModel();
                $res = $d->modify($data, ['uid' => $id]);
                if ($res === false) {
                    Db::rollback();
                    return 20;
                }
                // 修改评论表中用户的昵称、头像、性别
                $dc  = new TDynamicCommentModel();
                $res = $dc->modify($data, ['uid' => $id]);
                if ($res === false) {
                    Db::rollback();
                    return 30;
                }
                // 修改朋友表中用户的昵称、头像、性别
                $f   = new TFriendModel();
                $res = $f->modify($fda1, ['uid1' => $id]);
                if ($res === false) {
                    Db::rollback();
                    return 40;
                }
                $res = $f->modify($fda2, ['uid2' => $id]);
                if ($res === false) {
                    Db::rollback();
                    return 30;
                }
            }
            // 全部修改成功则提交事务
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return 40;
        }
    }
}
