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
     * 用户入驻
     * @author 贺强
     * @time   2018-10-29 10:09:52
     * @param  int   $type      用户类型
     * @param  array $user_data 用户基本信息
     * @param  array $attr_data 陪玩师扩展信息
     * @return int              返回添加结果
     */
    public function admission($user_data, $attr_data = null)
    {
        Db::startTrans();
        try {
            $res = $this->add($user_data);
            if (!$res) {
                Db::rollback();
                return 7;
            }
            if ($user_data['type'] == 2) {
                $ua = new UserAttrModel();
                foreach ($attr_data as &$attr) {
                    $attr['uid'] = $res;
                }
                $res = $ua->addArr($attr_data);
                if (!$res) {
                    DB::rollback();
                    return 8;
                }
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return 9;
        }
    }
}
