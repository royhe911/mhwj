<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    '/'                => 'admin/Admin/index',
    '/login'           => 'admin/Admin/login', // 后台登录页
    '/admin'           => 'admin/Admin/index', // 后台首页
    '/upload'          => 'admin/Upload/upload_img', // 上传文件
    '/admin/doLogin'   => 'admin/Admin/do_login', // 后台登录操作
    '/admin/index_v1'  => 'admin/Admin/index_v1', // 后台首页欢迎页
    '/admin/list'      => 'admin/Admin/lists', // 后台管理员列表
    '/admin/add'       => 'admin/Admin/add', // 后台添加管理员
    '/admin/edit'      => 'admin/Admin/edit', // 后台添加管理员
    '/admin/operation' => 'admin/Admin/operation', // 后台操作管理员
    '/menu/list'       => 'admin/Menu/lists', // 菜单列表
    '/menu/edit'       => 'admin/Menu/edit', // 编辑菜单
    '/menu/add'        => 'admin/Menu/add', // 添加菜单
    '/menu/del'        => 'admin/Menu/del', // 删除菜单
    '/menu/power'      => 'admin/Menu/power', // 菜单权限管理
    '/set/list'        => 'admin/Set/lists', // 轮播图列表
    '/set/add'         => 'admin/Set/add', // 添加轮播图
    '/game/list'       => 'admin/Game/lists', // 游戏列表
    '/game/add'        => 'admin/Game/add', // 添加游戏
    '/game/edit'       => 'admin/Game/edit', // 修改游戏
    '/game/del'        => 'admin/Game/del', // 删除游戏
    '/user/list'       => 'admin/User/lists', // 用户列表

    /*--------   api 开始   --------*/
    '/api/carousel'    => 'api/Api/get_carousel', // 获取轮播轮播图
    '/api/admission'   => 'api/Api/user_admission', // 用户入驻
    /*--------   api 结束  --------*/
];
