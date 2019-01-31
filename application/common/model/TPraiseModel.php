<?php
namespace app\common\model;

use app\common\model\TNoticeModel;
use think\Db;

/**
 * TPraiseModel类
 * @author 贺强
 * @time   2019-01-22 19:33:12
 */
class TPraiseModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 't_praise';
    }

    /**
     * 执行赞
     * @author 贺强
     * @time   2019-01-22 19:40:04
     * @param  array $param 赞参数
     */
    public function do_zan($param)
    {
        Db::startTrans();
        try {
            $id   = $param['obj_id'];
            $uid  = $param['uid'];
            $type = intval($param['type']);
            $ndat = ['type' => 3, 'obj_id' => $id, 'tid' => $uid, 'addtime' => time()];
            switch ($type) {
                case 1:
                    $model = new TUserModel();
                    break;
                case 2:
                    $model = new TDynamicModel();
                    // 赋值动态ID
                    $ndat['did'] = $id;
                    break;
                case 3:
                    $model = new TDynamicCommentModel();
                    break;
            }
            $u    = new TUserModel();
            $user = $u->getModel(['id' => $uid], ['nickname', 'avatar', 'status']);
            if (empty($user) || $user['status'] === 44) {
                Db::rollback();
                return 40;
            }
            $ndat['nickname'] = $user['nickname'];
            $ndat['avatar']   = $user['avatar'];
            // 获取点赞对象
            $data = $model->getModel(['id' => $id], true, '', true);
            if (!$data) {
                Db::rollback();
                return 10;
            }
            if ($type === 3) {
                $ndat['did'] = $data['did'];
                // 获取评论的动态
                $d  = new TDynamicModel();
                $dy = $d->getModel(['id' => $data['did']], ['uid']);
                if ($dy) {
                    $ndat['uid'] = $dy['uid'];
                }
            } elseif ($type === 2) {
                $ndat['uid'] = $data['uid'];
            }
            $where = ['obj_id' => $id, 'uid' => $uid, 'type' => $type];
            $count = $this->getCount($where);
            $n     = new TNoticeModel();
            if (!$count) {
                $n->add($ndat);
                $res = $model->increment('zan_count', ['id' => $id]);
            } else {
                $n->delByWhere(['type' => 3, 'tid' => $uid, 'obj_id' => $id]);
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
