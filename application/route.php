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
    '/order/uorders'      => 'admin/Order/uorders', // 房间订单
    '/order/udetail'      => 'admin/Order/udetail', // 房间订单详情
    '/order/porders'      => 'admin/Order/porders', // 房间订单
    '/order/pdetail'      => 'admin/Order/pdetail', // 房间订单详情
    '/order/morders'      => 'admin/Order/morders', // 房间订单
    '/order/mdetail'      => 'admin/Order/mdetail', // 房间订单详情
    '/order/complete'     => 'admin/Order/complete', // 完成订单
    '/order/cashlist'     => 'admin/Order/cash_list', // 提现列表
    '/order/cashdetail'   => 'admin/Order/cash_detail', // 提现详情
    '/order/auditors'     => 'admin/Order/auditors', // 提现审核
    '/goods/list'         => 'admin/Goods/lists', // 商品列表
    '/goods/add'          => 'admin/Goods/add', // 添加商品
    '/goods/edit'         => 'admin/Goods/edit', // 编辑商品
    '/goods/editsort'     => 'admin/Goods/editsort', // 编辑商品排序
    '/goods/operate'      => 'admin/Goods/operate', // 操作商品
    '/goods/editlucky'    => 'admin/Goods/editlucky', // 设置幸运儿
    '/goods/editknife'    => 'admin/Goods/editknife', // 修改需刀数
    '/goods/prizes'       => 'admin/Goods/prizes', // 砍价成功列表
    '/goods/ffjp'         => 'admin/Goods/ffjp', // 发放奖品

    /*--------   api 开始   --------*/
    '/api/carousel'       => 'api/Api/get_carousel', // 获取轮播轮播图
    '/api/login'          => 'api/Api/user_login', // 获取OPENID
    '/api/sync'           => 'api/Api/sync_userinfo', // 同步用户信息
    '/api/userinfo'       => 'api/Api/get_userinfo', // 获取用户信息
    '/api/masterinfo'     => 'api/Api/get_master_info', // 获取陪玩师信息
    '/api/mastercount'    => 'api/Api/get_master_count', // 陪玩师成绩
    '/api/upload'         => 'admin/Upload/upload', // 上传文件
    '/api/games'          => 'api/Api/get_games', // 游戏列表
    '/api/gamepara'       => 'api/Api/get_game_para', // 游戏大段位
    '/api/roompara'       => 'api/Api/get_room_para', // 房间游戏段位
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
    '/api/yulelist'       => 'api/Api/get_yule_list', // 娱乐榜
    '/api/comment'        => 'api/Api/user_comment', // 玩家评论陪玩师
    /*--------   api 结束  --------*/
    /*--------   支付 api 开始  --------*/
    '/api/pay/morder'     => 'api/Pay/add_master_order', // 房主下单
    '/api/pay/uorder'     => 'api/Pay/add_user_order', // 玩家下单
    '/api/pay/preodr'     => 'api/Pay/preorder', // 预下单
    '/api/pay/uodrs'      => 'api/Pay/get_user_order', // 玩家订单
    '/api/pay/userpay'    => 'api/Pay/user_pay', // 房间支付
    '/api/pay/odrsta'     => 'api/Pay/modify_order', // 修改订单状态
    '/api/pay/mposta'     => 'api/Pay/master_porder', // 陪玩师完成订制订单
    '/api/pay/mpostu'     => 'api/Pay/master_porder', // 陪玩师完成订制订单
    '/api/pay/ppord'      => 'api/Pay/personal_preorder', // 订制下单
    '/api/pay/personpay'  => 'api/Pay/person_pay', // 订制支付
    '/api/pay/popay'      => 'api/Pay/person_ord_pay', // 订制预支付
    '/api/pay/tasks'      => 'api/Pay/person_task', // 任务订单
    '/api/pay/robb'       => 'api/Pay/robbing', // 陪玩师抢单
    '/api/pay/robbnotice' => 'api/Pay/robb_notice', // 抢单成功通知
    '/api/pay/porder'     => 'api/Pay/get_person_order', // 获取玩家订制订单
    '/api/pay/coupons'    => 'api/Pay/get_user_coupon', // 获取玩家优惠卷
    '/api/pay/consume'    => 'api/Pay/get_consume_log', // 获取玩家消费记录
    '/api/pay/moneylog'   => 'api/Pay/get_money_log', // 提现记录
    '/api/pay/income'     => 'api/Pay/get_master_income', // 收入记录
    '/api/pay/masterord'  => 'api/Pay/get_master_order', // 陪玩师房间订单
    '/api/pay/masterpord' => 'api/Pay/get_master_pord', // 陪玩师订制订单
    '/api/pay/pordinfo'   => 'api/Pay/get_pord_info', // 订制订单详情
    '/api/pay/pordstatus' => 'api/Pay/get_pord_status', // 订制订单状态
    '/api/pay/uordinfo'   => 'api/Pay/get_uord_info', // 房间玩家订单详情
    '/api/pay/mordinfo'   => 'api/Pay/get_mord_info', // 房间陪玩师订单详情
    '/api/pay/callback'   => 'api/Pay/pay_callback', // 支付回调
    /*--------   支付 api 结束  --------*/

    /*--------   订制 api 开始  --------*/
    '/api/person/inroom'  => 'api/Person/come_in_room', // 进入私聊房间
    '/api/person/addchat' => 'api/Person/add_chat', // 添加私聊
    '/api/person/rooms'   => 'api/Person/get_person_room', // 获取私聊房间
    '/api/pay/cancelord'  => 'api/Pay/cancel_order', // 取消订单
    /*--------   订制 api 结束  --------*/

    /*--------   微信支付 api 开始  --------*/
    '/api/pay/prepay'     => 'api/Notify/prepay', // 预支付
    '/api/pay/notify'     => 'api/Notify/pay_notify', // 回调
    '/api/pay/paydata'    => 'api/Pay/save_pay_data', // 支付成功前端回调
    '/api/pay/refund'     => 'api/Notify/refund', // 退款
    '/api/pay/r_notify'   => 'api/Notify/refund_notify', // 退款回调
    '/api/pay/apply'      => 'api/Pay/apply_cash', // 申请提现
    /*--------   微信支付 api 结束  --------*/

    /*--------   砍价 api 开始  --------*/
    '/api/kj/test'        => 'api/Kanjia/test', // 砍价算法
    '/api/kj/launch'      => 'api/Kanjia/launch', // 发起砍价
    '/api/kj/helpchop'    => 'api/Kanjia/help_chop', // 帮忙砍价
    '/api/kj/helplist'    => 'api/Kanjia/get_help_list', // 帮砍帮
    '/api/kj/skin'        => 'api/Kanjia/get_skin', // 皮肤列表
    '/api/kj/kjinfo'      => 'api/Kanjia/get_kj_info', // 砍价信息
    '/api/kj/knifelist'   => 'api/Kanjia/knife_list', // 快刀榜
    '/api/kj/kjlist'      => 'api/Kanjia/get_kj_list', // 砍价榜
    /*--------   砍价 api 结束  --------*/
];
