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
    '/upload'          => 'admin/Upload/upload_img', // 上传图片
    '/admin/doLogin'   => 'admin/Admin/do_login', // 后台登录操作
    '/admin/index_v1'  => 'admin/Admin/index_v1', // 后台首页欢迎页
    '/admin/list'      => 'admin/Admin/lists', // 后台管理员列表
    '/admin/add'       => 'admin/Admin/add', // 后台添加管理员
    '/admin/edit'      => 'admin/Admin/edit', // 后台添加管理员
    '/admin/operation' => 'admin/Admin/operation', // 操作管理员
    '/menu/list'       => 'admin/Menu/lists', // 菜单列表
    '/menu/edit'       => 'admin/Menu/edit', // 编辑菜单
    '/menu/add'        => 'admin/Menu/add', // 添加菜单
    '/menu/del'        => 'admin/Menu/del', // 删除菜单
    '/menu/power'      => 'admin/Menu/power', // 菜单权限管理
    '/set/list'        => 'admin/Set/lists', // 轮播图列表
    '/set/add'         => 'admin/Set/add', // 添加轮播图
    '/set/del'         => 'admin/Set/del', // 添加轮播图
    '/set/edit'        => 'admin/Set/edit', // 编辑轮播图
    '/set/notices'     => 'admin/Set/notices', // 公告列表
    '/set/addnotice'   => 'admin/Set/add_notice', // 添加公告
    '/set/editnotice'  => 'admin/Set/edit_notice', // 修改公告
    '/game/list'       => 'admin/Game/lists', // 游戏列表
    '/game/add'        => 'admin/Game/add', // 添加游戏
    '/game/edit'       => 'admin/Game/edit', // 修改游戏
    '/game/editsort'   => 'admin/Game/editsort', // 修改游戏排序
    '/game/del'        => 'admin/Game/del', // 删除游戏
    '/user/list'       => 'admin/User/lists', // 用户列表
    '/user/detail'     => 'admin/User/detail', // 用户详情
    '/user/auditor'    => 'admin/User/auditor', // 审核用户

    /*--------   api 开始   --------*/
    '/api/carousel'    => 'api/Api/get_carousel', // 获取轮播轮播图
    '/api/login'       => 'api/Api/user_login', // 获取OPENID
    '/api/sync'        => 'api/Api/sync_userinfo', // 同步用户信息
    '/api/userinfo'    => 'api/Api/get_userinfo', // 获取用户信息
    '/api/upload'      => 'admin/Upload/upload', // 上传文件
    '/api/games'       => 'api/Api/get_games', // 游戏列表
    '/api/gameconfig'  => 'api/Api/get_game_config', // 技能水平
    '/api/addgame'     => 'api/Api/add_game', // 添加游戏
    '/api/examine'     => 'api/Api/user_examine', // 获取服务段位
    '/api/systip'      => 'api/Api/user_tip', // 系统消息
    '/api/feedback'    => 'api/Api/user_feedback', //用户反馈
    '/api/usergames'   => 'api/Api/get_user_games', // 用户游戏列表
    '/api/editgame'    => 'api/Api/edit_game', // 获取修改的游戏
    '/api/vericode'    => 'api/Api/get_vericode', // 获取验证码
    '/api/notice'      => 'api/Api/get_notice', // 获取公告
    /*--------   api 结束  --------*/
];
