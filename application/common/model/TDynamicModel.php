<?php
namespace app\common\model;

use think\Db;

/**
 * TUserDynamicModel类
 * @author 贺强
 * @time   2019-01-22 16:27:00
 */
class TDynamicModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 't_dynamic';
    }

    /**
     * 删除动态
     * @author 贺强
     * @time   2019-01-23 16:07:04
     * @param  integer $id 要删除动态ID
     */
    public function delDynamic($id)
    {
        Db::startTrans();
        try {
            // 查询数据是否存在
            $dynamic = $this->getModel(['id' => $id], true, '', true);
            if (!$dynamic) {
                Db::rollback();
                return 10;
            }
            // 删除对应的评论
            $dc  = new TDynamicCommentModel();
            $res = $dc->delByWhere(['did' => $id]);
            if ($res === false) {
                Db::rollback();
                return 30;
            }
            // 相应话题的发布量减 1
            if (!empty($dynamic['topic'])) {
                $t  = new TTopicModel();
                $res = $t->decrement('count', ['id' => ['in', $dynamic['topic']]]);
                if (!$res) {
                    Db::rollback();
                    return 40;
                }
            }
            // 删除数据
            $res = $this->delById($id);
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
