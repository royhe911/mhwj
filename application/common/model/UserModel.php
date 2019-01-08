<?php
namespace app\common\model;

use think\Db;

/**
 * UserModel类
 * @author 贺强
 * @time   2018-10-26 16:14:46
 */
class UserModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_user';
    }

    /**
     * 给陪玩师点赞
     * @author 贺强
     * @time   2019-01-08 10:19:24
     * @param  array $param 点赞参数
     */
    public function praise($param)
    {
        Db::startTrans();
        try {
            $sql  = "select * from m_user where id={$param['master_id']} for update";
            $data = Db::query($sql);
            if (!$data) {
                Db::rollback();
                return 10;
            }
            $res = $this->increment('praise', ['id' => $param['master_id']]);
            if (!$res) {
                Db::rollback();
                return 20;
            }
            $param['addtime'] = time();
            // 添加点赞
            $p   = new PraiseMasterModel();
            $res = $p->add($param);
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
