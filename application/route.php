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
    '/'                   => 'admin/Admin/index',
    '/login'              => 'admin/Admin/login', // 后台登录页
    '/admin'              => 'admin/Admin/index', // 后台首页
    '/upload'             => 'admin/Upload/upload_img', // 上传图片
    '/admin/doLogin'      => 'admin/Admin/do_login', // 后台登录操作
    '/admin/index_v1'     => 'admin/Admin/index_v1', // 后台首页欢迎页
    '/admin/list'         => 'admin/Admin/lists', // 后台管理员列表
    '/admin/add'          => 'admin/Admin/add', // 后台添加管理员
    '/admin/edit'         => 'admin/Admin/edit', // 后台添加管理员
    '/admin/operate'      => 'admin/Admin/operate', // 操作管理员
    '/menu/list'          => 'admin/Menu/lists', // 菜单列表
    '/menu/edit'          => 'admin/Menu/edit', // 编辑菜单
    '/menu/add'           => 'admin/Menu/add', // 添加菜单
    '/menu/del'           => 'admin/Menu/del', // 删除菜单
    '/menu/power'         => 'admin/Menu/power', // 菜单权限管理
    '/set/list'           => 'admin/Set/lists', // 轮播图列表
    '/set/add'            => 'admin/Set/add', // 添加轮播图
    '/set/del'            => 'admin/Set/del', // 添加轮播图
    '/set/edit'           => 'admin/Set/edit', // 编辑轮播图
    '/set/notices'        => 'admin/Set/notices', // 公告列表
    '/set/addnotice'      => 'admin/Set/add_notice', // 添加公告
    '/set/editnotice'     => 'admin/Set/edit_notice', // 修改公告
    '/game/list'          => 'admin/Game/lists', // 游戏列表
    '/game/add'           => 'admin/Game/add', // 添加游戏
    '/game/edit'          => 'admin/Game/edit', // 修改游戏
    '/game/editsort'      => 'admin/Game/editsort', // 修改游戏排序
    '/game/del'           => 'admin/Game/del', // 删除游戏
    '/user/list'          => 'admin/User/lists', // 用户列表
    '/user/detail'        => 'admin/User/detail', // 用户详情
    '/user/auditor'       => 'admin/User/auditor', // 审核用户
    '/user/operate'       => 'admin/User/operate', // 设置用户
    '/room/list'          => 'admin/Room/lists', // 房间列表
    '/room/operate'       => 'admin/Room/operate', // 设置房间

    /*--------   api 开始   --------*/
    '/api/carousel'       => 'api/Api/get_carousel', // 获取轮播轮播图
    '/api/login'          => 'api/Api/user_login', // 获取OPENID
    '/api/sync'           => 'api/Api/sync_userinfo', // 同步用户信息
    '/api/userinfo'       => 'api/Api/get_userinfo', // 获取用户信息
    '/api/upload'         => 'admin/Upload/upload', // 上传文件
    '/api/games'          => 'api/Api/get_games', // 游戏列表
    '/api/gameconfig'     => 'api/Api/get_game_config', // 技能水平
    '/api/addgame'        => 'api/Api/add_game', // 添加游戏
    '/api/examine'        => 'api/Api/user_examine', // 获取服务段位
    '/api/systip'         => 'api/Api/user_tip', // 系统消息
    '/api/feedback'       => 'api/Api/user_feedback', //用户反馈
    '/api/usergames'      => 'api/Api/get_user_games', // 用户游戏列表
    '/api/editgame'       => 'api/Api/edit_game', // 获取修改的游戏
    '/api/notice'         => 'api/Api/get_notice', // 获取公告
    '/api/vericode'       => 'api/Api/get_vericode', // 获取验证码
    '/api/checkcode'      => 'api/Api/check_vericode', // 检查验证码
    '/api/addroom'        => 'api/Api/add_room', // 创建房间
    '/api/roomlist'       => 'api/Api/get_room_list', // 房间列表
    '/api/roominfo'       => 'api/Api/get_room_info', // 房间信息
    '/api/roomsta'        => 'api/Api/modify_room_status', // 修改房间状态
    '/api/userlist'       => 'api/Api/get_user_list', // 用户列表
    '/api/inroom'         => 'api/Api/come_in_room', // 进入房间
    '/api/quitroom'       => 'api/Api/quit_room', // 退出房间
    '/api/closeroom'      => 'api/Api/close_room', // 关闭房间
    '/api/setseat'        => 'api/Api/set_seat', // 关闭位置
    '/api/addchat'        => 'api/Api/add_chat', // 添加聊天记录
    '/api/chatlog'        => 'api/Api/get_chat_log', // 聊天记录
    '/api/ready'          => 'api/Api/user_ready', // 玩家准备
    '/api/richlist'       => 'api/Api/get_rich_list', // 土豪榜
    '/api/effilist'       => 'api/Api/get_effi_list', // 效率榜
    '/api/praiselist'     => 'api/Api/get_praise_list', // 好评榜
    '/api/comment'        => 'api/Api/user_comment', // 玩家评论陪玩师
    /*--------   api 结束  --------*/
    /*--------   支付 api 开始  --------*/
    '/api/pay/morder'     => 'api/Pay/add_master_order', // 房主下单
    '/api/pay/uorder'     => 'api/Pay/add_user_order', // 玩家下单
    '/api/pay/preodr'     => 'api/Pay/preorder', // 预下单
    '/api/pay/uodrs'      => 'api/Pay/get_user_order', // 玩家订单
    '/api/pay/userpay'    => 'api/Pay/person_pay', // 房间支付
    '/api/pay/odrsta'     => 'api/Pay/modify_order', // 修改订单状态
    '/api/pay/ppord'      => 'api/Pay/personal_preorder', // 订制下单
    '/api/pay/prepay'     => 'api/Pay/prepay', // 预支付
    '/api/pay/personpay'  => 'api/Pay/person_pay', // 订制支付
    '/api/pay/popay'      => 'api/Pay/person_ord_pay', // 订制预支付
    '/api/pay/tasks'      => 'api/Pay/person_task', // 任务订单
    '/api/pay/robb'       => 'api/Pay/robbing', // 陪玩师抢单
    '/api/pay/porder'     => 'api/Pay/get_person_order', // 获取玩家订制订单
    '/api/pay/coupons'    => 'api/Pay/get_user_coupon', // 获取玩家优惠卷
    '/api/pay/consume'    => 'api/Pay/get_consume_log', // 获取玩家消费记录
    '/api/pay/moneylog'   => 'api/Pay/get_money_log', // 提现记录
    '/api/pay/masterord'  => 'api/Pay/get_master_order', // 陪玩师房间订单
    '/api/pay/masterpord' => 'api/Pay/get_master_pord', // 陪玩师打制订单
    '/api/pay/pordinfo'   => 'api/Pay/get_pord_info', // 订制订单详情
    '/api/pay/uordinfo'   => 'api/Pay/get_uord_info', // 房间玩家订单详情
    '/api/pay/mordinfo'   => 'api/Pay/get_mord_info', // 房间陪玩师订单详情
    /*--------   支付 api 结束  --------*/
    /*--------   订制 api 开始  --------*/
    '/api/person/inroom'  => 'api/Person/come_in_room', // 进入私聊房间
    '/api/person/addchat' => 'api/Person/add_chat', // 添加私聊
    '/api/person/rooms'   => 'api/Person/get_person_room', // 获取私聊房间
    '/api/pay/cancelord'  => 'api/Pay/cancel_order', // 取消订单
    /*--------   订制 api 结束  --------*/

    '/crontab/test'       => 'common/Order/',
];
