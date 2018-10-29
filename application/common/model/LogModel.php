<?php
namespace app\common\model;

use think\Session;

class LogModel extends CommonModel
{
    public function __construct()
    {
        $this->table = 'm_log';
    }

    const TYPE_ADD_USER     = 1; // 添加用户
    const TYPE_DELETE_USER  = 2; // 删除用户
    const TYPE_DISABLE_USER = 3; // 禁用用户
    const TYPE_ENABLE_USER  = 4; // 启用用户
    const TYPE_EDIT_USER    = 5; // 修改用户
    const TYPE_ADD_MENU     = 6; // 添加菜单
    const TYPE_DELETE_MENU  = 7; // 删除菜单
    const TYPE_EDIT_MENU    = 8; // 编辑菜单
    const TYPE_POWER        = 9; // 菜单权限分配

    /**
     * 写操作日志
     * @author  贺强
     * @time    2018-10-25 14:03:35
     * @param   array      $data 要写入的数据
     */
    public function addLog($data)
    {
        $admin           = Session::get('admin');
        $data['addtime'] = time();
        $data['uid']     = $admin['id'];
        $data['uname']   = $admin['nickname'];
        $this->add($data);
    }
}
