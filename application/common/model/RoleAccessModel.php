<?php
namespace app\common\model;

use think\Db;

class RoleAccessModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_role_access';
    }

    /**
     * 修改角色权限
     * @param  array $param 参数，包括 角色ID和菜单ID
     * @return int          返回修改结果
     */
    public function updateRolePower($param = [])
    {
        if (empty($param['role_id'])) {
            return 1;
        }
        Db::startTrans();
        try {
            $role_id = $param['role_id'];
            $this->delByWhere(['role_id' => $role_id]);
            $data = [];
            if (!empty($param['menu_ids'])) {
                foreach ($param['menu_ids'] as $menu_id) {
                    $data[] = ['role_id' => $role_id, 'menu_id' => $menu_id];
                }
                $this->addArr($data);
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return 2;
        }
    }
}
