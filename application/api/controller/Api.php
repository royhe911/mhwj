<?php
namespace app\api\controller;

use app\common\model\ChatModel;
use app\common\model\ChatUserModel;
use app\common\model\CouponModel;
use app\common\model\FeedbackModel;
use app\common\model\GameConfigModel;
use app\common\model\GameModel;
use app\common\model\MasterOrderModel;
use app\common\model\MessageModel;
use app\common\model\NoticeModel;
use app\common\model\PersonMasterOrderModel;
use app\common\model\PersonOrderModel;
use app\common\model\RoomMasterModel;
use app\common\model\RoomModel;
use app\common\model\RoomUserModel;
use app\common\model\UserAttrModel;
use app\common\model\UserEvaluateModel;
use app\common\model\UserInviteModel;
use app\common\model\UserLoginLogModel;
use app\common\model\UserModel;
use app\common\model\UserOrderModel;
use app\common\model\VericodeModel;
use Qcloud\Sms\SmsSingleSender;

/**
 * Api-控制器
 * @author 贺强
 * @time   2018-10-26 14:12:39
 */
class Api extends \think\Controller
{
    private $param = [];

    /**
     * 构造函数
     * @author 贺强
     * @time   2018-10-30 09:56:51
     */
    public function __construct()
    {
        $param = file_get_contents('php://input');
        // file_put_contents('/www/wwwroot/www_dragontang_com/public/logs.log', "\n".date('Y年m月d') . "\n" . $param . "\n\n", FILE_APPEND);
        $param = json_decode($param, true);
        if (empty($param['vericode'])) {
            echo json_encode(['status' => 300, 'info' => '非法参数', 'data' => null]);exit;
        }
        $vericode = $param['vericode'];
        unset($param['vericode']);
        // ksort($param);
        // $str = '';
        // foreach ($param as $key => $p) {
        //     if (is_array($p)) {
        //         ksort($p);
        //         foreach ($p as $r) {
        //             if (is_array($r)) {
        //                 ksort($r);
        //                 foreach ($r as $m) {
        //                     $str .= $m;
        //                 }
        //             } else {
        //                 $str .= $r;
        //             }
        //         }
        //     } else {
        //         $str .= $p;
        //     }
        // }
        $new_code = md5(config('MD5_PARAM'));
        if ($vericode !== $new_code) {
            echo json_encode(['status' => 100, 'info' => '非法参数', 'data' => null]);exit;
        }
        $this->param = $param;
    }

    /**
     * 获取轮播图
     * @author 贺强
     * @time   2018-10-26 14:14:50
     * @param  NoticeModel $n NoticeModel 实例
     * @return string         返回 json 串
     */
    public function get_carousel(NoticeModel $n)
    {
        $count = 3;
        $param = $this->param;
        if (!empty($param['count'])) {
            $count = $param['count'];
        }
        $where = ['is_delete' => 0, 'status' => 0, 'type' => 1];
        $list  = $n->getList($where, '`name`,`url`', "1,$count", "sort");
        if (!empty($list)) {
            foreach ($list as &$item) {
                if (!empty($item['url'])) {
                    $item['url'] = config('WEBSITE') . $item['url'];
                }
            }
            $msg = ['status' => 0, 'info' => '获取成功', 'data' => $list];
        } else {
            $msg = ['status' => 4, 'info' => '暂无数据', 'data' => null];
        }
        echo json_encode($msg);exit;
    }

    /**
     * 获取公告
     * @author 贺强
     * @time   2018-11-05 10:58:18
     * @param  NoticeModel $n NoticeModel 实例
     */
    public function get_notice(NoticeModel $n)
    {
        $count = 10;
        $param = $this->param;
        if (!empty($param['count'])) {
            $count = $param['count'];
        }
        $where = ['is_delete' => 0, 'status' => 0, 'type' => 2];
        $list  = $n->getList($where, '`name`,`content`', "1,$count", "sort");
        if (!empty($list)) {
            $msg = ['status' => 0, 'info' => '获取成功', 'data' => $list];
        } else {
            $msg = ['status' => 4, 'info' => '暂无数据', 'data' => null];
        }
        echo json_encode($msg);exit;
    }

    /**
     * 用户登录并调用微信接口获取 openid 并保存
     * @author 贺强
     * @time   2018-10-30 14:12:35
     * @param  UserModel $u UserModel 实例
     * @return int          返回用户 ID
     */
    public function user_login(UserModel $u)
    {
        $param = $this->param;
        if (empty($param['js_code'])) {
            echo json_encode(['status' => 1, 'info' => 'js_code 参数不能为空', 'data' => null]);exit;
        }
        $js_code = $param['js_code'];
        $appid   = config('APPID_PLAYER');
        $secret  = config('APPSECRET_PLAYER');
        if (!empty($param['type']) && intval($param['type']) === 2) {
            $appid  = config('APPID_ACCOMPANY');
            $secret = config('APPSECRET_ACCOMPANY');
        }
        $url  = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$js_code}&grant_type=authorization_code";
        $data = $this->curl($url);
        $data = json_decode($data, true);
        if (empty($data['openid'])) {
            echo json_encode(['status' => 2, 'info' => 'code 过期', 'data' => null]);exit;
        }
        $user = $u->getModel(['openid' => $data['openid']]);
        if (!empty($user)) {
            $data['login_time'] = time();
            $data['count']      = $user['count'] + 1;
            $res                = $u->modify($data, ['id' => $user['id']]);
            if ($res) {
                if (!empty($param['log_data'])) {
                    $ull              = new UserLoginLogModel();
                    $ld               = $param['login_data'];
                    $ld['uid']        = $user['id'];
                    $ld['login_time'] = time();
                    $ull->add($ld);
                }
                // 用户是否认证，0未认证  1已认证  2审核中  4审核未通过
                $is_certified = 0;
                if ($user['type'] === 2) {
                    if ($user['status'] === 8) {
                        $is_certified = 1;
                    } elseif ($user['status'] === 1) {
                        $is_certified = 2;
                    } elseif ($user['status'] === 4) {
                        $is_certified = 4;
                    }
                }
                $msg = ['status' => 0, 'info' => '登录成功', 'data' => ['id' => $user['id'], 'mobile' => $user['mobile'], 'is_certified' => $is_certified]];
            } else {
                $msg = ['status' => 3, 'info' => '登录失败', 'data' => null];
            }
        } else {
            $data['type']       = $param['type'];
            $data['addtime']    = time();
            $data['login_time'] = time();
            $id                 = $u->add($data);
            if ($id) {
                $cdata = ['uid' => $id, 'type' => 1, 'money' => 5, 'over_time' => time() + config('COUPONTERM') * 20 * 3600, 'addtime' => time()];
                $c     = new CouponModel();
                $c->add($cdata);
                $msg = ['status' => 0, 'info' => '登录成功', 'data' => ['id' => $id, 'mobile' => '', 'is_certified' => 0]];
            } else {
                $msg = ['status' => 4, 'info' => '登录失败', 'data' => null];
            }
        }
        echo json_encode($msg);exit;
    }

    /**
     * 同步用户信息
     * @author 贺强
     * @time   2018-10-30 16:22:54
     * @param  UserModel $u UserModel 实例
     * @return bool         返回同步结果
     */
    public function sync_userinfo(UserModel $u)
    {
        $param = $this->param;
        if (empty($param['id'])) {
            echo json_encode(['status' => 1, 'info' => '参数缺失', 'data' => null]);exit;
        }
        $param['updatetime'] = time();
        // 修改信息
        $res = $u->modify($param, ['id' => $param['id']]);
        if ($res !== false) {
            $msg = ['status' => 0, 'info' => '同步成功', 'data' => null];
        } else {
            $msg = ['status' => 4, 'info' => '同步失败', 'data' => null];
        }
        echo json_encode($msg);exit;
    }

    /**
     * 提交审核
     * @author 贺强
     * @time   2018-11-01 14:31:17
     * @param  UserModel $u UserModel 实例
     */
    public function user_examine(UserModel $u)
    {
        $param = $this->param;
        if (empty($param['id'])) {
            $msg = ['status' => 1, 'info' => '参数缺失', 'data' => null];
        } elseif (empty($param['mobile'])) {
            $msg = ['status' => 8, 'info' => '手机号不能为空', 'data' => null];
        } elseif (empty($param['code'])) {
            $msg = ['status' => 9, 'info' => '验证码不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $mobile = $param['mobile'];
        $code   = $param['code'];
        $msg    = [];
        $v      = new VericodeModel();
        $vcode  = $v->getModel(['mobile' => "v_$mobile", 'is_used' => 0]);
        if (empty($vcode)) {
            $msg = ['status' => 2, 'info' => '无效手机号', 'data' => null];
        } elseif ($vcode['vericode'] !== $code) {
            $msg = ['status' => 3, 'info' => '验证码错误', 'data' => null];
        } else {
            unset($param['code']);
            // $v->delByWhere(['mobile' => "v_$mobile"]);
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        if (empty($param['avatar'])) {
            $msg = ['status' => 2, 'info' => '头像不能为空', 'data' => null];
        } elseif (empty($param['nickname'])) {
            $msg = ['status' => 3, 'info' => '昵称不能为空', 'data' => null];
        } elseif (empty($param['sex'])) {
            $msg = ['status' => 4, 'info' => '性别不能为空', 'data' => null];
        } elseif (empty($param['birthday'])) {
            $msg = ['status' => 5, 'info' => '生日不能为空', 'data' => null];
        } elseif (empty($param['introduce'])) {
            $msg = ['status' => 6, 'info' => '简介不能为空', 'data' => null];
        } elseif (empty($param['tape'])) {
            $msg = ['status' => 7, 'info' => '录音地址不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $param['updatetime'] = time();
        $param['status']     = 1;
        // 修改信息
        $id  = $param['id'];
        $res = $u->modify($param, ['id' => $id]);
        if ($res !== false) {
            $data = ['type' => 1, 'uid' => $id, 'title' => '系统消息', 'content' => '正在审核，请稍后查看', 'addtime' => time()];
            $m    = new MessageModel();
            $m->add($data);
            $msg = ['status' => 0, 'info' => '提交成功', 'data' => null];
        } else {
            $msg = ['status' => 44, 'info' => '提交失败', 'data' => null];
        }
        echo json_encode($msg);exit;
    }

    /**
     * 获取用户信息
     * @author 贺强
     * @time   2018-10-30 17:40:20
     * @param  UserModel $u UserModel 实例
     * @return string       返回用户信息 json 串
     */
    public function get_userinfo(UserModel $u)
    {
        $param = $this->param;
        if (empty($param['id'])) {
            echo json_encode(['status' => 1, 'info' => '参数缺失']);exit;
        }
        $user = $u->getModel(['id' => $param['id']], 'id,nickname,`type`,avatar,contribution');
        if ($user) {
            $msg = ['status' => 0, 'info' => '获取成功', 'data' => $user];
        } else {
            $msg = ['status' => 4, 'info' => '获取失败', 'data' => null];
        }
        echo json_encode($msg);exit;
    }

    /**
     * 获取玩家列表
     * @author 贺强
     * @time   alt+t
     * @param  UserModel $u [description]
     * @return [type]       [description]
     */
    public function get_user_list(UserModel $u)
    {
        $param = $this->param;
        $where = ['is_delete' => 0];
        if (!empty($param['type'])) {
            $where['type'] = $param['type'];
        }
        // 分页参数
        $page     = 1;
        $pagesize = 10;
        $param    = $this->param;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $list = $u->getList($where, true, "$page,$pagesize");
        foreach ($list as &$item) {
            # code...
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 陪玩师成绩
     * @author 贺强
     * @time   2018-11-30 14:45:56
     */
    public function get_master_count()
    {
        $param = $this->param;
        if (empty($param['master_id'])) {
            $msg = ['status' => 1, 'info' => '陪玩师ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $master_id = $param['master_id'];
        // 今日接单数
        $data  = [];
        $start = strtotime(date('Y-m-d'));
        $end   = strtotime(date('Y-m-d', strtotime('+1 day')));
        $phere = ['master_id' => $master_id, 'addtime' => ['between', [$start, $end]]];
        $pmo   = new PersonMasterOrderModel();
        // 订制订单数
        $pcount = $pmo->getCount($phere);
        $rhere  = ['uid' => $master_id, 'addtime' => ['between', [$start, $end]]];
        $r      = new RoomModel();
        $rcount = $r->getCount($rhere);
        // 今日接单数
        $data['count'] = $pcount + $rcount;
        // 累计收益
        $u    = new UserModel();
        $user = $u->getModel(['id' => $master_id]);
        // 累计收益
        $data['acc_money'] = $user['acc_money'];
        $data['order_num'] = '';
        $data['room']      = null;
        $data['person']    = null;
        // 正在进行中的房间
        $room      = $r->getModel(['uid' => $master_id, 'status' => ['in', '0,1,5,6,8']]);
        $is_master = 1;
        if (empty($room)) {
            $rm   = new RoomMasterModel();
            $rmst = $rm->getModel(['uid' => $master_id, 'is_delete' => 0]);
            if ($rmst) {
                $room = $r->getModel(['id' => $rmst['room_id']]);
            }
            $is_master = 0;
        }
        if ($room) {
            $rmdt = ['room_id' => $room['id'], 'room_name' => $room['name'], 'master_avatar' => $user['avatar'], 'master_nickname' => $user['nickname'], 'master_count' => $room['master_count'], 'in_master_count' => $room['in_master_count'], 'count' => $room['count'] + $room['master_count'], 'in_count' => $room['in_count'] + $room['in_master_count'], 'status' => $room['status'], 'is_master' => $is_master];
            // 正在进行中的房间
            $data['room'] = $rmdt;
        } else {
            $person = $pmo->getModel(['master_id' => $master_id, 'status' => ['<>', 10]], ['order_id', 'status']);
            if ($person) {
                $po     = new PersonOrderModel();
                $porder = $po->getModel(['id' => $person['order_id']]);
                // 正在进行中的私聊房间
                $person['order_num'] = $porder['order_num'];
            }
            $data['person'] = $person;
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $data]);exit;
    }

    /**
     * 获取陪玩师详情
     * @author 贺强
     * @time   2018-11-29 18:51:43
     * @param  UserModel $u UserModel 实例
     */
    public function get_master_info(UserModel $u)
    {
        $param = $this->param;
        if (empty($param['id'])) {
            $msg = ['status' => 1, 'info' => '陪玩师ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid    = $param['id'];
        $master = $u->getModel(['id' => $uid], ['id', 'avatar', 'nickname', 'introduce', 'wx', 'qq']);
        if ($master) {
            // 查询陪玩师的接单数
            $r     = new RoomModel();
            $count = $r->getCount(['uid' => $uid]);
            // 接单数
            $master['number'] = 0;
            if ($count) {
                $master['number'] = $count;
            }
            // 查询计算陪玩师的好评率
            $ue    = new UserEvaluateModel();
            $count = $ue->getCount(['master_id' => $uid]);
            if ($count > 0) {
                $gcoun = $ue->getCount(['master_id' => $uid, 'score' => ['egt', 80]]);
                $ratio = round($gcoun / $count * 100, 2);
                if ($ratio > 0) {
                    $ratio .= '%';
                }
            } else {
                $ratio = 0;
            }
            // 评论数和好评率
            $master['comment'] = $count;
            $master['ratio']   = $ratio;
            // 查询陪玩师的认证图
            $master['album'] = null;
            // 取陪玩师属性
            $ua   = new UserAttrModel();
            $attr = $ua->getModel(['uid' => $master['id']]);
            if (!empty($attr['level_url'])) {
                $album = explode(',', $attr['level_url']);
                if (!empty($album)) {
                    foreach ($album as &$albm) {
                        if (strpos($albm, 'http://') === false && strpos($albm, 'https://') === false) {
                            $albm = config('WEBSITE') . $albm;
                        }
                    }
                }
                $master['album'] = $album;
            }
            // 查询陪玩师陪玩的游戏
            $g    = new GameModel();
            $game = $g->getModel(['id' => $attr['game_id']], ['name', 'url']);
            if ($game) {
                $master['gamename'] = $game['name'];
                $gameurl            = $game['url'];
                if (strpos($gameurl, 'http://') === false && strpos($gameurl, 'https://') === false) {
                    $gameurl = config('WEBSITE') . $gameurl;
                }
                $master['gameurl'] = $gameurl;
            } else {
                $master['gamename'] = '';
                $master['gameurl']  = '';
            }
            // 查询陪玩师的游戏段位
            $gc   = new GameConfigModel();
            $conf = $gc->getModel(['game_id' => $attr['game_id'], 'para_id' => $attr['curr_para']], ['para_str']);
            if ($conf) {
                $master['para_str'] = $conf['para_str'];
            } else {
                $master['para_str'] = '';
            }
            $master['price'] = 4;
            $master['room']  = null;
            // 查询该陪玩师有没有正在开的房间
            $room = $r->getModel(['uid' => $uid, 'status' => ['in', '0,1,5,6,8']], ['id room_id', 'name', 'type', 'para_min', 'para_max', 'num', 'price', 'total_money', 'in_count', 'count', 'in_master_count', 'master_count', 'addtime', 'status']);
            if ($room) {
                if (!empty($room['addtime'])) {
                    $room['addtime'] = date('Y-m-d H:i:s', $room['addtime']);
                }
                $master['room'] = $room;
            }
            echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $master]);exit;
        }
        echo json_encode(['status' => 4, 'info' => '陪玩师不存在', 'data' => null]);exit;
    }

    /**
     * 获取游戏列表
     * @author 贺强
     * @time   2018-10-31 12:00:48
     * @param  GameModel $g GameModel 实例
     */
    public function get_games(GameModel $g)
    {
        $where = ['is_delete' => 0];
        // 分页参数
        $page     = 1;
        $pagesize = 100;
        $param    = $this->param;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $list = $g->getList($where, 'id,identify,`name`,url', "$page,$pagesize", 'sort');
        if ($list) {
            foreach ($list as &$item) {
                if (!empty($item['url'])) {
                    $item['url'] = config('WEBSITE') . $item['url'];
                }
            }
            $msg = ['status' => 0, 'info' => '获取成功', 'data' => $list];
        } else {
            $msg = ['status' => 4, 'info' => '暂无数据', 'data' => null];
        }
        echo json_encode($msg);exit;
    }

    /**
     * 添加游戏
     * @author 贺强
     * @time   2018-10-31 12:19:41
     * @param  UserAttrModel $ua UserAttrModel 实例
     */
    public function add_game(UserAttrModel $ua)
    {
        $param = $this->param;
        if (empty($param['uid']) || empty($param['game_id'])) {
            $msg = ['status' => 1, 'info' => '参数缺失', 'data' => null];
        } elseif (empty($param['curr_para'])) {
            $msg = ['status' => 2, 'info' => '当前段位不能为空', 'data' => null];
        } elseif (empty($param['play_type'])) {
            $msg = ['status' => 4, 'info' => '陪玩类型不能为空', 'data' => null];
        } elseif (empty($param['level_url'])) {
            $msg = ['status' => 5, 'info' => '水平截图不能为空', 'data' => null];
        } elseif (intval($param['play_type']) === 1 && empty($param['logo'])) {
            $msg = ['status' => 6, 'info' => '头像不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $userAttr = $ua->getModel(['uid' => $param['uid'], 'game_id' => $param['game_id'], 'play_type' => $param['play_type']]);
        if ($userAttr) {
            $param['status'] = 1;
            // 修改技能
            $res = $ua->modify($param, ['uid' => $param['uid'], 'game_id' => $param['game_id']]);
        } else {
            $res = $ua->add($param);
        }
        if ($res !== false) {
            $msg = ['status' => 0, 'info' => '添加成功', 'data' => null];
        } else {
            $msg = ['status' => 44, 'info' => '添加失败', 'data' => null];
        }
        echo json_encode($msg);exit;
    }

    /**
     * 获取修改的游戏
     * @author 贺强
     * @time   2018-11-02 18:46:29
     * @param  UserAttrModel $ua UserAttrModel 实例
     */
    public function edit_game(UserAttrModel $ua)
    {
        $id   = $this->param['id'];
        $attr = $ua->getModel(['id' => $id], 'id,curr_para,play_para,play_type,level_url');
        if ($attr) {
            if (!empty($attr['level_url'])) {
                $attr['level_url'] = config('WEBSITE') . $attr['level_url'];
            }
            $msg = ['status' => 0, 'info' => '获取成功', 'data' => $attr];
        } else {
            $msg = ['status' => 4, 'info' => '数据错误', 'data' => null];
        }
        echo json_encode($msg);exit;
    }

    /**
     * 获取用户的技能列表
     * @author 贺强
     * @time   2018-11-02 18:21:32
     * @param  UserAttrModel $ua UserAttrModel 实例
     */
    public function get_user_games(UserAttrModel $ua)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            echo json_encode(['status' => 1, 'info' => '用户ID不能为空', 'data' => null]);exit;
        }
        $uid  = $param['uid'];
        $u    = new UserModel();
        $user = $u->getModel(['id' => $uid]);
        if ($user['type'] !== 2 || $user['status'] !== 8) {
            echo json_encode(['status' => 2, 'info' => '审核未通过', 'data' => null]);exit;
        }
        $list = $ua->getList(['uid' => $uid], 'id,game_id,curr_para,play_para,play_type,level_url');
        if ($list) {
            $g     = new GameModel();
            $games = $g->getList(['is_delete' => 0], 'id,identify,`name`,url');
            $games = array_column($games, null, 'id');
            foreach ($list as $k => &$item) {
                if (!empty($games[$item['game_id']])) {
                    $game              = $games[$item['game_id']];
                    $item['game_name'] = $game['name'];
                    $item['identify']  = $game['identify'];
                    if (!empty($game['url'])) {
                        $item['url'] = config('WEBSITE') . $game['url'];
                    } else {
                        $item['url'] = '';
                    }
                } else {
                    unset($list[$k]);
                }
            }
            $msg = ['status' => 0, 'info' => '获取成功', 'data' => $list];
        } else {
            $msg = ['status' => 4, 'info' => '暂无技能', 'data' => null];
        }
        echo json_encode($msg);exit;
    }

    /**
     * 获取游戏大段位
     * @author 贺强
     * @time   2018-11-30 14:28:15
     * @param  GameConfigModel $gc GameConfigModel 实例
     */
    public function get_game_para(GameConfigModel $gc)
    {
        $param = $this->param;
        if (empty($param['game_id'])) {
            echo json_encode(['status' => 1, 'info' => '游戏ID不能为空', 'data' => null]);exit;
        }
        $where = ['game_id' => $param['game_id']];
        $list  = $gc->getList($where, ['game_id', 'para', 'para_des', 'price'], null, 'para', 'para');
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 获取房间可选段位
     * @author 贺强
     * @time   2018-12-03 15:24:46
     * @param  GameConfigModel $gc GameConfigModel 实例
     */
    public function get_room_para(GameConfigModel $gc)
    {
        $param = $this->param;
        if (empty($param['room_id'])) {
            echo json_encode(['status' => 1, 'info' => '房间ID不能为空', 'data' => null]);exit;
        }
        $where = ['id' => $param['room_id']];
        $r     = new RoomModel();
        $room  = $r->getModel($where, ['game_id', 'para_min', 'para_max']);
        $where = ['game_id' => $room['game_id'], 'para' => ['between', [$room['para_min'], $room['para_max']]]];
        $list  = $gc->getList($where, ['para_str']);
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 获取游戏技能
     * @author 贺强
     * @time   2018-11-01 09:31:43
     * @param  GameModel       $g  GameModel 实例
     * @param  GameConfigModel $gc GameConfigModel 实例
     */
    public function get_game_config(GameModel $g, GameConfigModel $gc)
    {
        $param = $this->param;
        if (empty($param['game_id'])) {
            echo json_encode(['status' => 1, 'info' => '游戏ID不能为空', 'data' => null]);exit;
        }
        $game_id = $param['game_id'];
        $where   = ['is_delete' => 0, 'id' => $game_id];
        $game    = $g->getModel($where, 'identify,url,demo_url1,demo_url2,demo_url3');
        if (!$game) {
            echo json_encode(['status' => 2, 'info' => '数据错误', 'data' => null]);exit;
        }
        $where_c = ['game_id' => $game_id];
        $list    = $gc->getList($where_c, 'game_id,para_id,para_str,price', null, 'para_id');
        if ($list) {
            $url = $game['url'];
            if (strpos($url, 'http://') === false && strpos($url, 'https://') === false) {
                $url = config('WEBSITE') . $url;
            }
            $data['url'] = $url;
            $demo_url1   = $game['demo_url1'];
            if (strpos($demo_url1, 'http://') === false && strpos($demo_url1, 'https://') === false) {
                $demo_url1 = config('WEBSITE') . $demo_url1;
            }
            $demo_url2 = $game['demo_url2'];
            if (strpos($demo_url2, 'http://') === false && strpos($demo_url2, 'https://') === false) {
                $demo_url2 = config('WEBSITE') . $demo_url2;
            }
            $demo_url3 = $game['demo_url3'];
            if (strpos($demo_url3, 'http://') === false && strpos($demo_url3, 'https://') === false) {
                $demo_url3 = config('WEBSITE') . $demo_url3;
            }
            $data['demo_url'] = [$demo_url1, $demo_url2, $demo_url3];
            $data['para']     = config($game['identify']);
            $data['config']   = $list;
            $msg              = ['status' => 0, 'info' => '获取成功', 'data' => $data];
        } else {
            $msg = ['status' => 4, 'info' => '数据错误', 'data' => null];
        }
        echo json_encode($msg);exit;
    }

    /**
     * 系统消息
     * @author 贺强
     * @time   2018-11-02 10:02:45
     * @param  MessageModel $u MessageModel 实例
     */
    public function user_tip(MessageModel $m)
    {
        $param = $this->param;
        if (empty($param['id'])) {
            echo json_encode(['status' => 1, 'info' => '参数缺失', 'data' => null]);exit;
        }
        $id = $param['id'];
        if (!preg_match('/^\d+$/', $id)) {
            echo json_encode(['status' => 2, 'info' => '非法参数', 'data' => null]);exit;
        }
        // 分页参数
        $page     = 1;
        $pagesize = 100;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $list = $m->getList(['uid' => $id, 'type' => 1], 'title,content,addtime', "$page,$pagesize", 'addtime desc');
        if ($list) {
            foreach ($list as &$item) {
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 用户反馈
     * @author 贺强
     * @time   2018-11-02 11:17:09
     * @param  FeedbackModel $f FeedbackModel 实例
     */
    public function user_feedback(FeedbackModel $f)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            echo json_encode(['status' => 1, 'info' => '反馈用户ID不能为空', 'data' => null]);exit;
        }
        if (empty(ltrim(rtrim($param['content'])))) {
            echo json_encode(['status' => 2, 'info' => '反馈内容不能为空', 'data' => null]);exit;
        }
        $param['addtime'] = time();
        $res              = $f->add($param);
        if ($res) {
            $msg = ['status' => 0, 'info' => '反馈成功', 'data' => null];
        } else {
            $msg = ['status' => 4, 'info' => '反馈失败', 'data' => null];
        }
        echo json_encode($msg);exit;
    }

    /**
     * 生成验证码
     * @author 贺强
     * @time   2018-11-02 16:52:16
     * @return string 返回验证码
     */
    public function get_vericode()
    {
        $param = $this->param;
        if (empty($param['mobile'])) {
            echo json_encode(['status' => 1, 'info' => '手机号不能为空', 'data' => null]);exit;
        }
        $v      = new VericodeModel();
        $mobile = $param['mobile'];
        $veric  = $v->getModel(['mobile' => "v_$mobile", 'is_used' => 0]);
        if ($veric) {
            $v->modifyField('addtime', time(), ['mobile' => "v_$mobile", 'addtime' => $veric['addtime']]);
        }
        $count = $v->getCount(['mobile' => "v_$mobile"]);
        if ($count >= 5) {
            echo json_encode(['status' => 11, 'info' => '同一个手机号一天只能接收5条短信', 'data' => null]);exit;
        }
        // $v->delByWhere(['mobile' => "v_$mobile"]);
        if ($veric) {
            $vericode = $veric['vericode'];
        } else {
            $num = 4;
            if (!empty($param['num'])) {
                $num = intval($param['num']);
            }
            $vericode = get_random_num($num);
        }
        $sms        = new SmsSingleSender(config('SDKAPPID'), config('APPKEY'));
        $templateId = 251806;
        $param      = [$vericode];
        $smsSign    = '';
        $res        = $sms->sendWithParam('86', $mobile, $templateId, $param, $smsSign, '', '');
        $res        = json_decode($res, true);
        if ($res['result'] === 0) {
            $v->add(['mobile' => "v_$mobile", 'vericode' => $vericode, 'addtime' => time()]);
            $msg = ['status' => 0, 'info' => '发送成功', 'data' => $vericode];
        } else {
            $msg = ['status' => 4, 'info' => '发送失败', 'data' => null];
        }
        echo json_encode($msg);exit;
    }

    /**
     * 检查验证码是否正确
     * @author 贺强
     * @time   2018-11-05 15:28:33
     */
    public function check_vericode(VericodeModel $v)
    {
        $param = $this->param;
        if (empty($param['code']) || empty($param['mobile']) || empty($param['uid'])) {
            echo json_encode(['status' => 1, 'info' => '非法参数', 'data' => null]);exit;
        }
        $mobile = $param['mobile'];
        $code   = $v->getModel(['mobile' => "v_$mobile", 'is_used' => 0]);
        if (empty($code)) {
            $msg = ['status' => 1, 'info' => '无效手机号', 'data' => null];
        } elseif (time() - $code['addtime'] > 300) {
            // $v->delByWhere(['mobile' => "v_$mobile"]);
            $msg = ['status' => 2, 'info' => '验证码过期', 'data' => null];
        } elseif ($code["vericode"] !== $param['code']) {
            $msg = ['status' => 3, 'info' => '验证码错误', 'data' => null];
        } else {
            $v->modifyField('status', 1, ['mobile' => "v_$mobile"]);
            // $v->delByWhere(['mobile' => "v_$mobile"]);
            $msg = ['status' => 0, 'info' => '验证成功', 'data' => null];
        }
        $u   = new UserModel();
        $res = $u->modifyField('mobile', $mobile, ['id' => $param['uid']]);
        if (!empty($param['invite_uid']) && $res) {
            $uidata = ['uid' => $param['invite_uid'], 'invited_uid' => $param['uid'], 'addtime' => time()];
            $ui     = new UserInviteModel();
            $ui->add($uidata);
        }
        echo json_encode($msg);exit;
    }

    /**
     * 创建房间
     * @author 贺强
     * @time   2018-11-05 16:51:21
     * @param  RoomModel $r RoomModel 实例
     */
    public function add_room(RoomModel $r)
    {
        // 访问时间限制
        $limit = $this->room_limit();
        $limit = false;
        if ($limit) {
            echo json_encode(['status' => 444, 'info' => "本活动将于{$limit['start_time']}-{$limit['end_time']}之间开启，点击预约！", 'data' => null]);exit;
        }
        $param = $this->param;
        if (empty($param['name'])) {
            $msg = ['status' => 8, 'info' => '房间名称不能为空', 'data' => null];
        } elseif (strlen($param['name']) > 24) {
            $msg = ['status' => 9, 'info' => '名称过长', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '陪玩师ID不能为空', 'data' => null];
        } elseif (empty($param['game_id'])) {
            $msg = ['status' => 2, 'info' => '游戏ID不能为空', 'data' => null];
        } elseif (empty($param['type'])) {
            $msg = ['status' => 3, 'info' => '房间类型不能为空', 'data' => null];
        } elseif (intval($param['type']) === 1 && empty($param['para_min'])) {
            $msg = ['status' => 12, 'info' => '最低服务段位不能为空', 'data' => null];
        } elseif (intval($param['type']) === 1 && empty($param['para_max'])) {
            $msg = ['status' => 13, 'info' => '最高服务段位不能为空', 'data' => null];
        } elseif (intval($param['type']) === 1 && intval($param['para_min']) > intval($param['para_max'])) {
            $msg = ['status' => 14, 'info' => '最低段位不能高于最高段位', 'data' => null];
        } elseif (intval($param['type']) === 2 && empty($param['price'])) {
            $msg = ['status' => 15, 'info' => '每小时价格不能为空', 'data' => null];
        } elseif (intval($param['type']) === 2 && intval($param['price']) > 999) {
            $msg = ['status' => 18, 'info' => '每小时价格只能是1-999', 'data' => null];
        } elseif (empty($param['region'])) {
            $msg = ['status' => 4, 'info' => '房间所属大区不能为空', 'data' => null];
        } elseif (empty($param['master_count']) || intval($param['master_count']) < 1 || intval($param['master_count']) > 4) {
            $msg = ['status' => 6, 'info' => '陪玩师人数只能为1-4人', 'data' => null];
        } elseif (empty($param['num']) || intval($param['num']) < 1 || intval($param['num']) > 5 || (intval($param['type']) === 2 && intval($param['num']) > 3)) {
            $str = '局数只能是1-5局';
            if (intval($param['type']) === 2) {
                $str = '小时数只能是1-3小时';
            }
            $msg = ['status' => 15, 'info' => $str, 'data' => null];
        } else {
            // 获取房间
            $count = $r->getCount(['is_delete' => 0, 'uid' => $param['uid'], 'status' => ['not in', '4,9,10']]);
            if ($count) {
                echo json_encode(['status' => 16, 'info' => '一次只能创建一个房间']);exit;
            }
            $pmo   = new PersonMasterOrderModel();
            $count = $pmo->getCount(['master_id' => $param['uid'], 'status' => ['<>', 10]]);
            if ($count) {
                echo json_encode(['status' => 17, 'info' => '您还有未完成的订制订单', 'data' => null]);exit;
            }
            $u    = new UserModel();
            $user = $u->getModel(['id' => $param['uid'], 'is_delete' => 0], 'type,`status`');
            if (!$user) {
                $msg = ['status' => 6, 'info' => '陪玩师不存在', 'data' => null];
            } elseif ($user['type'] !== 2 || $user['status'] !== 8) {
                $msg = ['status' => 7, 'info' => '您还未认证成为陪玩师，现在就去认证', 'data' => null];
            } elseif (intval($param['type']) === 1) {
                $ua       = new UserAttrModel();
                $userAttr = $ua->getModel(['uid' => $param['uid'], 'game_id' => $param['game_id'], 'play_type' => $param['type'], 'status' => 8], ['curr_para', 'play_type']);
                if (!$userAttr) {
                    $msg = ['status' => 10, 'info' => '您的审核还未通过，不能陪玩此游戏', 'data' => null];
                } elseif ($userAttr['play_type'] === 1 && $userAttr['curr_para'] < $param['para_min']) {
                    $msg = ['status' => 11, 'info' => '您的等级不够陪玩的等级', 'data' => null];
                }
            }
        }
        if (intval($param['type']) === 2) {
            $param['count']        = 1;
            $param['master_count'] = 1;
        }
        if (intval($param['type']) === 1) {
            $param['count'] = 5 - intval($param['master_count']);
        }
        $param['addtime'] = time();
        if (intval($param['type']) === 2) {
            $param['total_money'] = $param['price'] * $param['num'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $res = $r->add($param);
        if ($res) {
            $mord = ['uid' => $param['uid'], 'room_id' => $res, 'play_type' => $param['type'], 'game_id' => $param['game_id'], 'region' => $param['region'], 'order_num' => get_millisecond(), 'addtime' => time()];
            if (intval($param['type']) === 2) {
                $mord['order_money'] = $param['price'] * $param['num'];
            }
            $mo = new MasterOrderModel();
            $mo->add($mord);
            $msg = ['status' => 0, 'info' => '创建成功', 'data' => ['id' => $res, 'count' => $param['count'], 'in_count' => 1]];
        } else {
            $msg = ['status' => 44, 'info' => '创建失败', 'data' => null];
        }
        echo json_encode($msg);exit;
    }

    /**
     * 获取房间列表
     * @author 贺强
     * @time   2018-11-08 11:59:20
     * @param  RoomModel $r RoomModel 实例
     */
    public function get_room_list(RoomModel $r)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            echo json_encode(['status' => 1, 'info' => '玩家ID不能为空', 'data' => null]);exit;
        }
        $where = 'is_delete=0 and status in (0,1,5,6,8) and in_master_count=master_count';
        if (!empty($param['game_id'])) {
            $where .= " and game_id={$param['game_id']}";
        }
        if (!empty($param['region'])) {
            $where .= " and region={$param['region']}";
        }
        if (!empty($param['type'])) {
            $where .= " and `type`={$param['type']}";
        }
        if (isset($param['status'])) {
            $where .= " and `status`={$param['status']}";
        }
        // 分页参数
        $page     = 1;
        $pagesize = 10;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $count = 0;
        $list  = $r->getList($where, 'id,uid,name,game_id,type,para_min,para_max,price,num,total_money,region,in_count,count,in_master_count,master_count,status', "$page,$pagesize", 'addtime desc,status');
        if ($list) {
            $count    = $r->getCount($where);
            $uids     = array_column($list, 'uid');
            $game_ids = array_column($list, 'game_id');
            $jd_count = $r->getList(['uid' => ['in', $uids]], ['count(*) c,uid']);
            $jd_count = array_column($jd_count, 'c', 'uid');
            $u        = new UserModel();
            $users    = $u->getList(['is_delete' => 0, 'id' => ['in', $uids], 'type' => 2], 'id,nickname,avatar');
            $users    = array_column($users, null, 'id');
            $ua       = new UserAttrModel();
            $attr_w   = ['status' => 8, 'uid' => ['in', $uids], 'game_id' => ['in', $game_ids], 'play_type' => $param['type']];
            $attrs    = $ua->getList($attr_w, ['uid', 'game_id', 'winning', 'level_url', 'logo']);
            $attr_arr = [];
            $levels   = [];
            $logos    = [];
            foreach ($attrs as $attr) {
                $attr_arr[$attr['uid']][$attr['game_id']] = $attr['winning'];
                // 取第一张水平截图
                $urls = explode(',', $attr['level_url']);
                $uurl = $urls[0];
                if (!empty($uurl) && strpos($uurl, 'http://') === false && strpos($uurl, 'https://') === false) {
                    $uurl = config('WEBSITE') . $uurl;
                }
                $levels[$attr['uid']][$attr['game_id']] = $uurl;
                if (!empty($attr['logo']) && strpos($attr['logo'], 'http://') === false && strpos($attr['logo'], 'https://') === false) {
                    $logos[$attr['uid']][$attr['game_id']] = config('WEBSITE') . $attr['logo'];
                }
            }
            $gc     = new GameConfigModel();
            $gclist = $gc->getList(['game_id' => ['in', $game_ids]], ['game_id', 'para', 'para_des', 'para_id', 'para_str', 'price']);
            $gparr  = [];
            $gcarr  = [];
            foreach ($gclist as $gci) {
                $gparr[$gci['game_id']][$gci['para']] = $gci['para_des'];
                $gcarr[$gci['game_id']][$gci['para']] = $gci['price'];
            }
            foreach ($list as &$item) {
                $item['total_count']    = $item['count'] + $item['master_count'];
                $item['in_total_count'] = $item['in_count'] + $item['in_master_count'];
                if (!empty($jd_count[$item['uid']])) {
                    $item['jd_count'] = $jd_count[$item['uid']];
                } else {
                    $item['jd_count'] = 0;
                }
                if (!empty($users[$item['uid']])) {
                    $item['nickname'] = $users[$item['uid']]['nickname'];
                    $item['avatar']   = $users[$item['uid']]['avatar'];
                } else {
                    $item['nickname'] = '';
                    $item['avatar']   = '';
                }
                if ($item['type'] === 1 && !empty($gcarr[$item['game_id']]) && !empty($gcarr[$item['game_id']][$item['para_min']])) {
                    $item['price'] = $gcarr[$item['game_id']][$item['para_min']];
                } elseif ($item['type'] === 2) {
                    # code...
                } else {
                    $item['price'] = 0;
                }
                if (!empty($gparr[$item['game_id']]) && !empty($gparr[$item['game_id']][$item['para_min']])) {
                    $item['para_min_str'] = $gparr[$item['game_id']][$item['para_min']];
                } else {
                    $item['para_min_str'] = '';
                }
                if (!empty($gparr[$item['game_id']]) && !empty($gparr[$item['game_id']][$item['para_max']])) {
                    $item['para_max_str'] = $gparr[$item['game_id']][$item['para_max']];
                } else {
                    $item['para_max_str'] = '';
                }
                if (!empty($attr_arr[$item['uid']]) && !empty($attr_arr[$item['uid']][$item['game_id']])) {
                    $item['winning'] = $attr_arr[$item['uid']][$item['game_id']];
                } else {
                    $item['winning'] = 0;
                }
                if (!empty($levels[$item['uid']]) && !empty($levels[$item['uid']][$item['game_id']])) {
                    $item['level_url'] = $levels[$item['uid']][$item['game_id']];
                } else {
                    $item['level_url'] = '';
                }
                if (!empty($logos[$item['uid']]) && !empty($logos[$item['uid']][$item['game_id']])) {
                    $item['logo'] = $logos[$item['uid']][$item['game_id']];
                } else {
                    $item['logo'] = '';
                }
            }
        }
        $user = null;
        $ru   = new RoomUserModel();
        $rou  = $ru->getModel(['uid' => $param['uid'], 'status' => ['in', '0,1,5,6']]);
        if (!empty($rou)) {
            $room = $r->getModel(['id' => $rou['room_id']]);
            $user = ['id' => $room['id'], 'count' => $room['count'] + $room['master_count'], 'in_count' => $room['in_count'] + $room['in_master_count']];
        }
        $po  = new PersonOrderModel();
        $pod = $po->getModel(['uid' => $param['uid'], 'status' => ['in', '7,8']], ['id']);
        if (!empty($pod)) {
            $user['porder']   = $pod['id'];
            $user['count']    = 2;
            $user['in_count'] = 2;
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => ['list' => $list, 'user' => $user, 'count' => $count]]);exit;
    }

    /**
     * 获取房间信息
     * @author 贺强
     * @time   2018-11-09 10:59:17
     * @param  RoomModel $r RoomModel 实例
     */
    public function get_room_info(RoomModel $r)
    {
        $param = $this->param;
        $type  = intval($param['type']);
        if (!empty($param['is_share']) && intval($param['is_share']) === 1) {
            $uid = $param['uid'];
            if ($type === 1) {
                $ru    = new RoomUserModel();
                $count = $ru->getCount(['room_id' => $param['room_id'], 'uid' => $uid]);
                if (!$count) {
                    echo json_encode(['status' => 14, 'info' => '选择段位', 'data' => null]);exit;
                }
            } elseif ($type === 2) {
                $count = $r->getCount(['id' => ['<>', $param['room_id']], 'uid' => $uid, 'status' => ['in', '0,1,5,6,8']]);
                if ($count) {
                    echo json_encode(['status' => 14, 'info' => '您已有正在进行中的房间', 'data' => null]);exit;
                }
                // $rm    = new RoomMasterModel();
                // $count = $rm->getCount(['uid' => $uid, 'is_delete' => 0, 'room_id' => ['<>', $param['room_id']]]);
                // if ($count) {
                //     echo json_encode(['status' => 14, 'info' => '您已有正在进行中的房间', 'data' => null]);exit;
                // }
                $ua    = new UserAttrModel();
                $count = $ua->getCount(['uid' => $uid, 'status' => 8, 'game_id' => $param['game_id']]);
                if (!$count) {
                    echo json_encode(['status' => 14, 'info' => '您还未认证此游戏，不能陪玩', 'data' => null]);exit;
                }
                $pm    = new PersonMasterOrderModel();
                $count = $pm->getCount(['master_id' => $uid, 'status' => ['<>', 10]]);
                if ($count) {
                    echo json_encode(['status' => 16, 'info' => '您还有未完成的订制订单', 'data' => null]);exit;
                }
            }
            $state = $this->come_in_room(true);
            if ($state !== true) {
                echo json_encode(['status' => $state, 'info' => '进入房间失败', 'data' => null]);exit;
            }
        }
        if (empty($param['room_id'])) {
            $msg = ['status' => 1, 'info' => '房间ID不能为空', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 2, 'info' => '用户ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $room_id = $param['room_id'];
        $uid     = intval($param['uid']);
        $ru      = new RoomUserModel();
        if ($type === 1) {
            $count = $ru->getCount(['room_id' => $room_id, 'uid' => $uid]);
            if (!$count) {
                echo json_encode(['status' => 11, 'info' => '您还未进入房间', 'data' => null]);exit;
            }
        }
        $room = $r->getModel(['id' => $room_id], 'id,uid,name,game_id,type,para_min,para_max,price,num,total_money,region,in_count,count,in_master_count,master_count,status room_status');
        if ($room) {
            $roomuser = $ru->getList(['room_id' => $room_id]);
            $uids     = array_column($roomuser, 'uid');
            $rm       = new RoomMasterModel();
            $masters  = $rm->getList(['room_id' => $room_id]);
            $mids     = array_column($masters, 'uid');
            $mids     = array_merge([$room['uid']], $mids);
            $uids     = array_merge($uids, $mids);
            if ($room['room_status'] === 10) {
                $msg = ['status' => 3, 'info' => '陪玩师已确认订单，房间已销毁，请到订单列表完成订单', 'data' => null];
            } elseif ($room['room_status'] === 9 || $room['room_status'] === 7) {
                $msg = ['status' => 5, 'info' => '有玩家未付款，房间已销毁，您的付款会在3个工作日内原路退还', 'data' => null];
            } elseif ($room['count'] === $room['in_count']) {
                if (!in_array($uid, $uids)) {
                    $msg = ['status' => 6, 'info' => '房间人数已满', 'data' => null];
                }
            }
            if (!empty($msg)) {
                echo json_encode($msg);exit;
            }
            $g    = new GameModel();
            $game = $g->getModel(['id' => $room['game_id']], ['name', 'url']);
            if ($game) {
                $gameurl = $game['url'];
                if (strpos($gameurl, 'http://') === false && strpos($gameurl, 'https://') === false) {
                    $gameurl = config('WEBSITE') . $gameurl;
                }
                $room['game_name'] = $game['name'];
                $room['game_url']  = $gameurl;
            } else {
                $room['game_name'] = '';
                $room['game_url']  = '';
            }
            if ($room['type'] === 1) {
                $room['type'] = '实力上分';
            } else {
                $room['type'] = '娱乐陪玩';
            }
            if ($room['region'] === 1) {
                $room['region'] = 'QQ';
            } elseif ($room['region'] === 2) {
                $room['region'] = '微信';
            }
            $gc     = new GameConfigModel();
            $gclist = $gc->getList(['game_id' => $room['game_id'], 'para_id' => ['in', [$room['para_min'], $room['para_max']]]], 'para_id,para_str');
            foreach ($gclist as $gci) {
                if ($room['para_min'] === $gci['para_id']) {
                    $room['para_min_str'] = $gci['para_str'];
                } elseif ($room['para_max'] === $gci['para_id']) {
                    $room['para_max_str'] = $gci['para_str'];
                }
            }
            $u       = new UserModel();
            $users   = $u->getList(['id' => ['in', $uids]], ['id', 'nickname', 'avatar', 'wx', 'qq']);
            $members = [];
            // 获取房间里玩家的状态
            $ustatus     = $ru->getList(['room_id' => $room_id, 'uid' => ['in', $uids]], 'uid,status,price,total_money');
            $total_money = 0;
            $price       = 0;
            foreach ($ustatus as $utta) {
                $total_money += $utta['total_money'];
                $price += $utta['price'];
            }
            $total_money /= count($mids);
            $price /= count($mids);
            $ustatarr = array_column($ustatus, null, 'uid');
            foreach ($users as $user) {
                if (!empty($ustatarr[$user['id']])) {
                    $usta       = $ustatarr[$user['id']];
                    $status_txt = '';
                    if ($usta['status'] === 0) {
                        $status_txt = '未准备';
                    } elseif ($usta['status'] === 1) {
                        $status_txt = '已准备';
                    } elseif ($usta['status'] === 6) {
                        $status_txt = '已支付';
                    }
                }
                if ($user['id'] === $uid) {
                    if (in_array($user['id'], $mids)) {
                        $room['total_money'] = $total_money;
                        $room['price']       = $price;
                    } else {
                        $room['total_money'] = $usta['total_money'];
                        $room['price']       = $usta['price'];
                        $room['status']      = $usta['status'];
                        $room['status_txt']  = $status_txt;
                    }
                }
                if (in_array($user['id'], $mids)) {
                    if ($user['id'] === $room['uid']) {
                        $user['master'] = 1;
                    } else {
                        $user['master'] = 0;
                    }
                    $members['master'][] = $user;
                } else {
                    if (!empty($usta)) {
                        $user['status']     = $usta['status'];
                        $user['status_txt'] = $status_txt;
                    }
                    $members['users'][] = $user;
                }
            }
            $room['members'] = $members;
            $c               = new ChatModel();
            $list            = $c->getJoinList([['m_chat_user c', 'a.id=c.chat_id']], ['a.room_id' => $room_id, 'c.uid' => $uid], ['a.uid', 'a.avatar', 'a.content'], '', 'c.addtime');
            $room['chatlog'] = $list;
            $msg             = ['status' => 0, 'info' => '获取成功', 'data' => $room];
        } else {
            $msg = ['status' => 4, 'info' => '房间不存在', 'data' => null];
        }
        echo json_encode($msg);exit;
    }

    /**
     * 修改房间状态
     * @author 贺强
     * @time   2018-11-20 09:40:58
     * @param  RoomModel $r RoomModel 实例
     */
    public function modify_room_status(RoomModel $r)
    {
        $param = $this->param;
        if (empty($param['room_id'])) {
            $msg = ['status' => 1, 'info' => '房间ID不能为空', 'data' => null];
        } elseif (empty($param['status'])) {
            $msg = ['status' => 3, 'info' => '要修改状态不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $room_id = $param['room_id'];
        $room    = $r->getModel(['id' => $room_id]);
        $status  = intval($param['status']);
        if ($room['status'] === 5 && $status === 5) {
            echo json_encode(['status' => 7, 'info' => '不能重复开始', 'data' => null]);exit;
        }
        $ru = new RoomUserModel();
        if ($status === 5) {
            $count = $ru->getCount(['room_id' => $room_id, 'status' => 0]);
            if ($count) {
                echo json_encode(['status' => 6, 'info' => '还有玩家未准备，不能开始', 'data' => null]);exit;
            }
        }
        $mo = new MasterOrderModel();
        if ($status === 8) {
            if ($room['status'] !== 6) {
                echo json_encode(['status' => 7, 'info' => '还有玩家未支付，不能开车', 'data' => null]);exit;
            }
            $morder = $mo->getModel(['room_id' => $room_id]);
            $rm     = new RoomMasterModel();
            $ms     = $rm->getList(['room_id' => $room_id], ['uid']);
            if (!empty($ms)) {
                $mids = array_column($ms, 'uid');
                $mny  = $morder['order_money'] / (count($mids) + 1);
                $mo->modifyField('order_money', $mny, ['id' => $morder['id']]);
                unset($morder['id']);
                $mdat = [];
                foreach ($mids as $md) {
                    $morder['order_num'] = get_millisecond() . $md;
                    $morder['uid']       = $md;
                    // 订单金额和完成金额
                    $morder['order_money']    = $mny;
                    $morder['complete_money'] = 0;
                    $mdat[]                   = $morder;
                }
                $mo->addArr($mdat);
            }
        }
        if ($status === 10) {
            if ($room['status'] !== 8) {
                echo json_encode(['status' => 22, 'info' => '游戏未开始，不能完成', 'data' => null]);exit;
            }
            $rm = new RoomMasterModel();
            $rm->modifyField('is_delete', 1, ['room_id' => $room_id]);
            $ch = new ChatModel();
            $ch->delByWhere(['room_id' => $room_id]);
            $cu = new ChatUserModel();
            $cu->delByWhere(['room_id' => $room_id]);
        }
        $res = $r->modifyField('status', $status, ['id' => $room_id]);
        if ($res === false) {
            echo json_encode(['status' => 40, 'info' => '修改失败', 'data' => null]);exit;
        }
        $mo->modifyField('status', $status, ['room_id' => $room_id]);
        if ($status === 5) {
            $ru->modifyField('status', 5, ['room_id' => $room_id]);
        }
        echo json_encode(['status' => 0, 'info' => '修改成功', 'data' => null]);exit;
    }

    /**
     * 进入房间
     * @author 贺强
     * @time   2018-11-09 10:27:20
     * @param  bool $is_share 是否是分享进入
     */
    public function come_in_room($is_share = false)
    {
        $param = $this->param;
        $res   = true;
        if (empty($param['room_id'])) {
            $res = 10;
            echo json_encode(['status' => 10, 'info' => '房间ID不能为空', 'data' => null]);exit;
        }
        $ru   = new RoomUserModel();
        $r    = new RoomModel();
        $room = $r->getModel(['id' => $param['room_id']], ['type']);
        if (empty($room)) {
            echo json_encode(['status' => 10, 'info' => '房间不存在', 'data' => null]);exit;
        }
        if (empty($param['uid'])) {
            $res = 20;
            $msg = ['status' => 20, 'info' => '用户ID不能为空', 'data' => null];
        } elseif (empty($param['type'])) {
            $res = 30;
            $msg = ['status' => 30, 'info' => '用户类型不能为空', 'data' => null];
        } elseif ($room['type'] === 1 && intval($param['type']) === 1 && empty($param['para_str'])) {
            $res = 40;
            $msg = ['status' => 40, 'info' => '段位不能为空', 'data' => null];
        } elseif (!empty($param['para_str'])) {
            $count = $ru->getCount(['room_id' => $param['room_id'], 'uid' => $param['uid']]);
            if ($count) {
                $msg = ['status' => 41, 'info' => '不能重复选择段位', 'data' => null];
            }
        }
        if (!empty($msg) && !$is_share) {
            echo json_encode($msg);exit;
        }
        $uo    = new UserOrderModel();
        $count = $uo->getCount(['room_id' => $param['room_id'], 'uid' => $param['uid'], 'status' => 10]);
        if ($count) {
            echo json_encode(['status' => 12, 'info' => '您已完成该房间任务，详情请查看订单', 'data' => null]);exit;
        }
        $count = $ru->getCount(['room_id' => ['<>', $param['room_id']], 'uid' => $param['uid'], 'status' => ['not in', '4,10']]);
        if ($count) {
            echo json_encode(['status' => 21, 'info' => '不能同时进两个房间', 'data' => null]);exit;
        }
        $po    = new PersonOrderModel();
        $count = $po->getCount(['uid' => $param['uid'], 'status' => ['in', '1,6,7']]);
        if ($count) {
            echo json_encode(['status' => 22, 'info' => '您有订制订单未完成，请先完成订制订单', 'data' => null]);exit;
        }
        $r   = new RoomModel();
        $res = $r->in_room($param);
        if ($is_share) {
            return $res;
        }
        if ($res !== true) {
            $msg = '进入房间失败';
            if ($res === 3) {
                $msg = '房间人数已满';
            } elseif ($res === 4) {
                $msg = '房间不存在';
            } elseif ($res === 11) {
                $msg = '游戏已结束';
            } elseif ($res === 12) {
                $msg = '有玩家未付款，房间已销毁，若您已付款，会在3个工作日内原路退还';
            } elseif ($res === 5) {
                $msg = '您还没有认证实力上分';
            }
            echo json_encode(['status' => $res, 'info' => $msg, 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '进入房间成功', 'data' => null]);exit;
    }

    /**
     * 退出房间
     * @author 贺强
     * @time   2018-11-09 16:52:45
     * @param  RoomModel $r RoomModel 实例
     */
    public function quit_room(RoomModel $r)
    {
        $param = $this->param;
        if (empty($param['room_id'])) {
            $msg = ['status' => 10, 'info' => '房间ID不能为空', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 20, 'info' => '用户ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $ru   = new RoomUserModel();
        $rusr = $ru->getModel(['room_id' => $param['room_id'], 'uid' => $param['uid']]);
        if (!empty($rusr)) {
            // 房主踢人参数
            if (!empty($param['is_kicking']) && intval($param['is_kicking']) === 1 && $rusr['status'] > 4) {
                echo json_encode(['status' => 22, 'info' => '您已点开始，不能踢', 'data' => null]);exit;
            }
            if ($rusr['status'] === 5) {
                $msg = ['status' => 23, 'info' => '房主已点开始，不能退出', 'data' => null];
            } elseif ($rusr['status'] === 6) {
                $msg = ['status' => 23, 'info' => '您已付款，不能退出', 'data' => null];
            }
            if (!empty($msg)) {
                echo json_encode($msg);exit;
            }
        }
        $res = $r->quit_room($param['room_id'], $param['uid']);
        if ($res !== true) {
            $msg = '';
            if ($res === 4) {
                $msg = '游戏已开始，不能退出';
            }
            echo json_encode(['status' => $res, 'info' => $msg, 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '退出成功', 'data' => null]);exit;
    }

    /**
     * 关闭/销毁房间
     * @author 贺强
     * @time   2018-11-12 10:02:20
     * @param  RoomModel     $r  RoomModel 实例
     * @param  RoomUserModel $ru RoomUserModel 实例
     */
    public function close_room(RoomModel $r, RoomUserModel $ru)
    {
        $param = $this->param;
        if (empty($param['room_id'])) {
            $msg = ['status' => 1, 'info' => '房间ID不能为空', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 2, 'info' => '陪玩师ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $room_id = $param['room_id'];
        $uid     = $param['uid'];
        $count   = $ru->getCount(['room_id' => $room_id]);
        if ($count) {
            echo json_encode(['status' => 4, 'info' => '房间里有其他玩家，不能关闭/退出']);exit;
        }
        $count = $r->getCount(['id' => $room_id, 'uid' => $uid]);
        if (!$count) {
            $rm  = new RoomMasterModel();
            $res = $rm->delByWhere(['uid' => $uid, 'room_id' => $room_id]);
            if (!$res) {
                echo json_encode(['status' => 3, 'info' => '您不是房主，无权关闭', 'data' => null]);exit;
            }
            $r->decrement('in_master_count', ['id' => $room_id]);
            echo json_encode(['status' => 0, 'info' => '退出房间成功', 'data' => null]);exit;
        }
        $res = $r->delById($room_id);
        if (!$res) {
            echo json_encode(['status' => 44, 'info' => '关闭失败', 'data' => null]);exit;
        }
        $c = new ChatModel();
        $c->delByWhere(['room_id' => $room_id]);
        $cu = new ChatUserModel();
        $cu->delByWhere(['room_id' => $room_id]);
        $mo = new MasterOrderModel();
        $mo->delByWhere(['room_id' => $room_id]);
        echo json_encode(['status' => 0, 'info' => '关闭成功', 'data' => null]);exit;
    }

    /**
     * 关闭/打开位置
     * @author 贺强
     * @time   2018-11-09 17:17:01
     * @param  RoomModel $r RoomModel 实例
     */
    public function set_seat(RoomModel $r)
    {
        $param = $this->param;
        if (empty($param['room_id'])) {
            $msg = ['status' => 10, 'info' => '房间ID不能为空', 'data' => null];
        } elseif (empty($param['type'])) {
            $msg = ['status' => 20, 'info' => '操作类型不能为空', 'data' => null];
        } elseif (intval($param['type']) !== 1 && intval($param['type']) !== 2) {
            $msg = ['status' => 30, 'info' => '非法操作', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $res = $r->set_seat($param['room_id'], $param['type']);
        if ($res !== true) {
            $msg = '操作失败';
            switch ($res) {
                case 2:
                    $msg = '至少要留一个玩家位置';
                    break;
                case 3:
                    $msg = '最多只能有5个位置';
                    break;
            }
            echo json_encode(['status' => $res, 'info' => $msg, 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '操作成功', 'data' => null]);exit;
    }

    /**
     * 添加聊天记录
     * @author 贺强
     * @time   2018-11-13 11:21:45
     * @param  ChatModel     $c  ChatModel 实例
     */
    public function add_chat(ChatModel $c)
    {
        $param = $this->param;
        if (empty($param['room_id'])) {
            $msg = ['status' => 1, 'info' => '房间ID不能为空', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 2, 'info' => '说话用户ID不能为空', 'data' => null];
        } elseif (empty($param['avatar'])) {
            $msg = ['status' => 3, 'info' => '说话用户头像不能为空', 'data' => null];
        } elseif (empty($param['content'])) {
            $msg = ['status' => 4, 'info' => '聊天内容不能为空', 'data' => null];
        }
        $param['addtime'] = time();
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $res = $c->add_chat($param);
        if (!$res) {
            echo json_encode(['status' => $res, 'info' => '添加失败', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '添加成功', 'data' => null]);exit;
    }

    /**
     * 获取聊天记录
     * @author 贺强
     * @time   2018-11-13 12:13:21
     * @param  ChatModel $c ChatModel 实例
     */
    public function get_chat_log(ChatModel $c)
    {
        $param = $this->param;
        if (empty($param['room_id'])) {
            $msg = ['status' => 1, 'info' => '房间ID不能为空', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 2, 'info' => '用户ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $list = $c->getJoinList([['m_chat_user c', 'a.id=c.chat_id']], ['a.room_id' => $param['room_id'], 'c.uid' => $param['uid']], ['a.uid', 'a.avatar', 'a.content'], '', 'a.addtime desc');
        if (!$list) {
            echo json_encode(['status' => 4, 'info' => '获取失败', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 玩家准备
     * @author 贺强
     * @time   2018-11-13 15:56:05
     * @param  RoomUserModel $ru RoomUserModel 实例
     */
    public function user_ready(RoomUserModel $ru)
    {
        $param = $this->param;
        if (empty($param['room_id'])) {
            $msg = ['status' => 1, 'info' => '房间ID不能为空', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 2, 'info' => '用户ID不能为空', 'data' => null];
        } elseif ($param['status'] === null) {
            $msg = ['status' => 3, 'info' => '设置状态不能为空', 'data' => null];
        }
        $param['ready_time'] = time();
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $res = $ru->modify($param, ['room_id' => $param['room_id'], 'uid' => $param['uid']]);
        if ($res === false) {
            echo json_encode(['status' => 44, 'info' => '操作失败', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '操作成功', 'data' => null]);exit;
    }

    /**
     * 玩家评论陪玩师
     * @author 贺强
     * @time   2018-11-20 14:09:35
     * @param  UserEvaluateModel $ue UserEvaluateModel 实例
     */
    public function user_comment(UserEvaluateModel $ue)
    {
        $param = $this->param;
        if (empty($param['master_id'])) {
            $msg = ['status' => 1, 'info' => '陪玩师ID不能为空', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 3, 'info' => '玩家ID不能为空', 'data' => null];
        } elseif (empty($param['type'])) {
            $msg = ['status' => 2, 'info' => '订单类型不能为空', 'data' => null];
        } elseif (empty($param['order_id'])) {
            $msg = ['status' => 4, 'info' => '订单ID不能为空', 'data' => null];
        } elseif (empty($param['content'])) {
            $msg = ['status' => 5, 'info' => '评论内容不能为空', 'data' => null];
        } elseif (empty($param['score'])) {
            $msg = ['status' => 7, 'info' => '评价分数不能为空', 'data' => null];
        }
        $param['addtime'] = time();
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $res = $ue->add($param);
        if (!$res) {
            echo json_encode(['status' => 40, 'info' => '评论失败', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '评论成功', 'data' => null]);exit;
    }

    /**
     * 获取玩家土豪榜
     * @author 贺强
     * @time   2018-11-20 10:18:04
     * @param  UserModel $u UserModel 实例
     */
    public function get_rich_list(UserModel $u)
    {
        $param    = $this->param;
        $where    = ['is_delete' => 0, 'type' => 1, 'nickname' => ['<>', ''], 'avatar' => ['<>', '']];
        $order    = ['contribution' => 'desc'];
        $page     = 1;
        $pagesize = 10;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $list = $u->getList($where, ['id', 'nickname', 'avatar', 'contribution score'], "$page,$pagesize", $order);
        if ($list) {
            foreach ($list as &$item) {
                $item['score'] = format_number($item['score']);
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 获取陪玩师效率榜
     * @author 贺强
     * @time   2018-11-20 10:55:53
     * @param  RoomModel $r RoomModel 实例
     */
    public function get_effi_list(RoomModel $r)
    {
        $param    = $this->param;
        $where    = ['status' => 10];
        $page     = 1;
        $pagesize = 10;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $list = $r->getList($where, ['uid', 'sum(num) score'], "$page,$pagesize", ['score' => 'desc'], 'uid');
        if (!empty($list)) {
            $uids  = array_column($list, 'uid');
            $u     = new UserModel();
            $users = $u->getList(['id' => ['in', $uids]], ['id master_id', 'nickname', 'avatar']);
            $users = array_column($users, null, 'master_id');
            foreach ($list as $k => &$item) {
                if (!empty($users[$item['uid']])) {
                    $score = $item['score'];
                    $item  = $users[$item['uid']];
                    // 陪玩师分数
                    $item['score'] = $score;
                } else {
                    unset($list[$k]);
                }
            }
            $list = array_merge($list);
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 获取陪玩师好评榜
     * @author 贺强
     * @time   2018-11-20 12:23:09
     * @param  UserEvaluateModel $ue UserEvaluateModel 实例
     */
    public function get_praise_list(UserEvaluateModel $ue)
    {
        $param    = $this->param;
        $page     = 1;
        $pagesize = 10;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $list = $ue->getList([], ['master_id', 'avg(score) score'], "$page,$pagesize", ['score' => 'desc'], 'master_id');
        if (!empty($list)) {
            $uids  = array_column($list, 'master_id');
            $u     = new UserModel();
            $users = $u->getList(['id' => ['in', $uids]], ['id', 'nickname', 'avatar']);
            $users = array_column($users, null, 'id');
            foreach ($list as $k => &$item) {
                $item['score'] = rtrim($item['score'], '0');
                $item['score'] = rtrim($item['score'], '.');
                if (!empty($users[$item['master_id']])) {
                    $master = $users[$item['master_id']];
                    if (empty($master['avatar'])) {
                        unset($list[$k]);
                        continue;
                    }
                    // 取得陪玩师昵称和头像
                    $item['nickname'] = $master['nickname'];
                    $item['avatar']   = $master['avatar'];
                } else {
                    unset($list[$k]);
                }
            }
            $list = array_merge($list);
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 获取娱乐陪玩师
     * @author 贺强
     * @time   2018-12-13 09:47:27
     * @param  UserModel $u UserModel 实例
     */
    public function get_yule_list(UserAttrModel $ua)
    {
        $param    = $this->param;
        $page     = 1;
        $pagesize = 6;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $where = ['status' => 8, 'play_type' => 2, 'level_url' => ['<>', '']];
        $count = $ua->getCount($where);
        $num   = ceil($count / $pagesize);
        $page  = mt_rand(1, $num);
        $list  = $ua->getList($where, ['uid', 'level_url'], "$page,$pagesize");
        if ($list) {
            $uids  = array_column($list, 'uid');
            $uo    = new UserOrderModel();
            $order = $uo->getList(['uid' => ['in', $uids], 'play_type' => 2], ['uid', 'count(*) c'], '', '', 'uid');
            $order = array_column($order, 'c', 'uid');
            $u     = new UserModel();
            $users = $u->getList(['id' => ['in', $uids], 'status' => 8], ['id', 'nickname', 'avatar']);
            $users = array_column($users, null, 'id');
            foreach ($list as $k => &$item) {
                if (!empty($users[$item['uid']])) {
                    $user = $users[$item['uid']];
                    // 属性赋值
                    $item['nickname'] = $user['nickname'];
                    $item['avatar']   = $user['avatar'];
                } else {
                    unset($list[$k]);
                }
                if (!empty($item['level_url'])) {
                    $level_url = explode(',', $item['level_url']);
                    foreach ($level_url as &$url) {
                        if (strpos($url, 'http://') === false && strpos($url, 'https://') === false) {
                            $url = config('WEBSITE') . $url;
                        }
                    }
                    $item['level_url'] = $level_url;
                }
                if (!empty($order[$item['uid']])) {
                    $item['count'] = $order[$item['uid']];
                } else {
                    $item['count'] = 0;
                }
            }
            shuffle($list);
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

}
