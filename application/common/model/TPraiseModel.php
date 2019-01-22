<?php
namespace app\common\model;

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
            $type = intval($param['type']);
            switch ($type) {
                case 1:
                    $model = new TUserModel();
                    break;
                case 2:
                    $model = new TUserDynamicModel();
                    break;
                case 3:
                    $model = new TUserDynamicCommentModel();
                    break;
            }
            $data = $model->getModel(['id' => $id], ['id'], '', true);
            if (!$data) {
                Db::rollback();
                return 10;
            }
            $where = ['obj_id' => $id, 'uid' => $param['uid'], 'type' => $type];
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
