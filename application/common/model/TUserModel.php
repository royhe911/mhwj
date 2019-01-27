<?php
namespace app\common\model;

use think\Db;

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

    /**
     * 同步用户信息
     * @author 贺强
     * @time   2019-01-23 12:07:09
     * @param  array $param 要同步的参数
     */
    public function syncinfo($param)
    {
        $data = [];
        $fda1 = [];
        $fda2 = [];
        $gdat = [];
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
        if (!empty($param['gid'])) {
            $g    = new TGameModel();
            $game = $g->getModel(['id' => $param['gid']]);
            $gdat = ['uid' => $id, 'name' => $game['name'], 'logo' => $game['logo'], 'online' => $param['online']];
            unset($param['gid'], $param['online']);
        }
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
                // 修改聊天表中用户的昵称、头像、性别
                $c   = new TChatModel();
                $res = $c->modify($data, ['uid' => $id]);
                if ($res === false) {
                    Db::rollback();
                    return 60;
                }
            }
            $f = new TFriendModel();
            $r = new TRoomModel();
            if (!empty($fda1)) {
                // 修改朋友表中用户的昵称、头像、性别
                $res = $f->modify($fda1, ['uid1' => $id]);
                if ($res === false) {
                    Db::rollback();
                    return 40;
                }
                // 修改房间表中用户的昵称、头像、性别
                $res = $r->modify($fda1, ['uid1' => $id]);
                if ($res === false) {
                    DB::rollback();
                    return 70;
                }
            }
            if (!empty($fda2)) {
                // 修改朋友表中用户的昵称、头像、性别
                $res = $f->modify($fda2, ['uid2' => $id]);
                if ($res === false) {
                    Db::rollback();
                    return 50;
                }
                // 修改房间表中用户的昵称、头像、性别
                $res = $r->modify($fda2, ['uid2' => $id]);
                if ($res === false) {
                    Db::rollback();
                    return 80;
                }
            }
            if (!empty($gdat)) {
                $ugm = $g->getCount(['uid' => $id]);
                if ($ugm) {
                    $g->modify($gdat, ['uid' => $id]);
                } else {
                    $g->add($gdat);
                }
            }
            // 全部修改成功则提交事务
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return 44;
        }
    }
}
