<?php
namespace app\api\controller;

use app\common\model\FeedbackModel;
use app\common\model\GameConfigModel;
use app\common\model\GameModel;
use app\common\model\MessageModel;
use app\common\model\NoticeModel;
use app\common\model\RoomModel;
use app\common\model\UserAttrModel;
use app\common\model\UserModel;
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
        $appid   = 'wxe6f37de8e1e3225e';
        $secret  = '357566bea005201ce062acaabd4a58e9';
        if (!empty($param['type']) && intval($param['type']) === 2) {
            $appid  = 'wxecd6bfdba0623aa5';
            $secret = '8ff39ccfde133942cd8933b240a79960';
        }
        $url  = "https://api.weixin.qq.com/sns/jscode2cookie?appid={$appid}&secret={$secret}&js_code={$js_code}&grant_type=authorization_code";
        $data = $this->curl($url);
        $data = json_decode($data, true);
        if (empty($data['openid'])) {
            echo json_encode(['status' => 2, 'info' => 'code 过期', 'data' => null]);exit;
        }
        $user = $u->getModel(['openid' => $data['openid']]);
        if (!empty($user)) {
            $data['login_time'] = time();
            $data['updatetime'] = time();
            $data['count']      = $user['count'] + 1;
            $res                = $u->modify($data, ['id' => $user['id']]);
            if ($res) {
                $msg = ['status' => 0, 'info' => '登录成功', 'data' => ['id' => $user['id'], 'mobile' => $user['mobile']]];
            } else {
                $msg = ['status' => 3, 'info' => '登录失败', 'data' => null];
            }
        } else {
            $data['type']       = $param['type'];
            $data['addtime']    = time();
            $data['login_time'] = time();
            $id                 = $u->add($data);
            if ($id) {
                $msg = ['status' => 0, 'info' => '登录成功', 'data' => ['id' => $id, 'mobile' => '']];
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
            echo json_encode(['status' => 1, 'info' => '参数缺失', 'data' => null]);exit;
        }
        if (empty($param['mobile'])) {
            echo json_encode(['status' => 8, 'info' => '手机号不能为空', 'data' => null]);exit;
        }
        $mobile = $param['mobile'];
        if (empty($param['code'])) {
            echo json_encode(['status' => 9, 'info' => '验证码不能为空', 'data' => null]);exit;
        }
        $code  = $param['code'];
        $msg   = [];
        $v     = new VericodeModel();
        $vcode = $v->getModel(['mobile' => "v_$mobile"]);
        if (empty($vcode)) {
            $msg = ['status' => 2, 'info' => '无效手机号', 'data' => null];
        } elseif ($vcode['vericode'] !== $code) {
            $msg = ['status' => 3, 'info' => '验证码错误', 'data' => null];
        } else {
            unset($param['code']);
            $v->delByWhere(['mobile' => "v_$mobile"]);
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        if (empty($param['avatar'])) {
            echo json_encode(['status' => 2, 'info' => '头像不能为空', 'data' => null]);exit;
        }
        if (empty($param['nickname'])) {
            echo json_encode(['status' => 3, 'info' => '昵称不能为空', 'data' => null]);exit;
        }
        if (empty($param['sex'])) {
            echo json_encode(['status' => 4, 'info' => '性别不能为空', 'data' => null]);exit;
        }
        if (empty($param['birthday'])) {
            echo json_encode(['status' => 5, 'info' => '生日不能为空', 'data' => null]);exit;
        }
        if (empty($param['introduce'])) {
            echo json_encode(['status' => 6, 'info' => '简介不能为空', 'data' => null]);exit;
        }
        if (empty($param['tape'])) {
            echo json_encode(['status' => 7, 'info' => '录音地址不能为空', 'data' => null]);exit;
        }
        $param['updatetime'] = time();
        $param['status']     = 1;
        // 修改信息
        $res = $u->modify($param, ['id' => $param['id']]);
        if ($res !== false) {
            $data = ['type' => 1, 'uid' => $param['id'], 'title' => '系统消息', 'content' => '正在审核，请稍后查看', 'addtime' => time()];
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
        $list = $g->getList($where, 'id,identify,`name`,url', "$page,$pagesize");
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
            echo json_encode(['status' => 1, 'info' => '参数缺失', 'data' => null]);exit;
        }
        if (empty($param['curr_para'])) {
            echo json_encode(['status' => 2, 'info' => '当前段位不能为空', 'data' => null]);exit;
        }
        if (empty($param['play_type'])) {
            echo json_encode(['status' => 4, 'info' => '陪玩类型不能为空', 'data' => null]);exit;
        }
        if (empty($param['level_url'])) {
            echo json_encode(['status' => 5, 'info' => '水平截图不能为空', 'data' => null]);exit;
        }
        $userAttr = $ua->getModel(['uid' => $param['uid'], 'game_id' => $param['game_id']]);
        if ($userAttr) {
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
            foreach ($list as &$item) {
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
                    $item['game_name'] = '';
                    $item['identify']  = '';
                    $item['url']       = '';
                }
            }
            $msg = ['status' => 0, 'info' => '获取成功', 'data' => $list];
        } else {
            $msg = ['status' => 4, 'info' => '暂无技能', 'data' => null];
        }
        echo json_encode($msg);exit;
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
        $game    = $g->getModel($where, 'identify,demo_url1,demo_url2');
        if (!$game) {
            echo json_encode(['status' => 2, 'info' => '数据错误', 'data' => null]);exit;
        }
        $where_c = ['game_id' => $game_id];
        $list    = $gc->getList($where_c, 'game_id,para_id,para_str', null, 'para_id');
        if ($list) {
            $data['para']   = config($game['identify']);
            $data['config'] = $list;
            $demo_url1      = $game['demo_url1'];
            if (strpos($demo_url1, 'http://') === false && strpos($demo_url1, 'https://') === false) {
                $demo_url1 = config('WEBSITE') . $demo_url1;
            }
            $demo_url2 = $game['demo_url2'];
            if (strpos($demo_url2, 'http://') === false && strpos($demo_url2, 'https://') === false) {
                $demo_url2 = config('WEBSITE') . $demo_url2;
            }
            $data['demo_url'] = [$demo_url1, $demo_url2];
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
            $msg = ['status' => 0, 'info' => '获取成功', 'data' => $list];
        } else {
            $msg = ['status' => 4, 'info' => '该用户暂无消息', 'data' => null];
        }
        echo json_encode($msg);exit;
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
        $v->delByWhere(['mobile' => "v_$mobile"]);
        $num = 4;
        if (!empty($param['num'])) {
            $num = intval($param['num']);
        }
        $vericode   = get_random_num($num);
        $sms        = new SmsSingleSender(config('SDKAPPID'), config('APPKEY'));
        $templateId = 221888;
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
        if (empty($param['code']) || empty($param['mobile'])) {
            echo json_encode(['status' => 1, 'info' => '非法参数', 'data' => null]);exit;
        }
        $mobile = $param['mobile'];
        $code   = $v->getModel(['mobile' => "v_$mobile"]);
        if (empty($code)) {
            $msg = ['status' => 1, 'info' => '无效手机号', 'data' => null];
        } elseif (time() - $code['addtime'] > 300) {
            $v->delByWhere(['mobile' => "v_$mobile"]);
            $msg = ['status' => 2, 'info' => '验证码过期', 'data' => null];
        } elseif ($code["vericode"] !== $param['code']) {
            $msg = ['status' => 3, 'info' => '验证码错误', 'data' => null];
        } else {
            $v->delByWhere(['mobile' => "v_$mobile"]);
            $msg = ['status' => 0, 'info' => '验证成功', 'data' => null];
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
        } elseif (empty($param['region'])) {
            $msg = ['status' => 4, 'info' => '房间所属大区不能为空', 'data' => null];
        } elseif (empty($param['count']) || intval($param['count']) < 2 || intval($param['count']) > 5) {
            $msg = ['status' => 5, 'info' => '房间人数只能是2-5人', 'data' => null];
        } elseif (empty($param['price'])) {
            $msg = ['status' => 14, 'info' => '每局价格不能为空', 'data' => null];
        } elseif (empty($param['num']) || intval($param['num']) < 1 || intval($param['num']) > 5) {
            $msg = ['status' => 15, 'info' => '局数不正确', 'data' => null];
        } else {
            $u    = new UserModel();
            $user = $u->getModel(['id' => $param['uid']], 'type,`status`');
            if (!$user) {
                $msg = ['status' => 6, 'info' => '陪玩师不存在', 'data' => null];
            } elseif ($user['type'] !== 2 || $user['status'] !== 8) {
                $msg = ['status' => 7, 'info' => '无权创建', 'data' => null];
            } else {
                $ua       = new UserAttrModel();
                $userAttr = $ua->getModel(['uid' => $param['uid'], 'game_id' => $param['game_id']], 'curr_para,play_type');
                if (!$userAttr) {
                    $msg = ['status' => 10, 'info' => '您不能陪玩此游戏', 'data' => null];
                } elseif ($userAttr['play_type'] === 2 || $userAttr['curr_para'] < $param['para_max'] || $param['para_max'] < $param['para_min']) {
                    $msg = ['status' => 11, 'info' => '您的等级不够陪玩的等级', 'data' => null];
                }
            }
        }
        $param['addtime'] = time();
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $res = $r->add($param);
        if ($res) {
            $msg = ['status' => 0, 'info' => '创建成功', 'data' => null];
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
        $where = ['is_delete' => 0];
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        if (!empty($param['region'])) {
            $where['region'] = $param['region'];
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
        $list = $r->getList($where, 'id,uid,name,game_id,type,para_min,para_max,price,num,total_money,region,in_count,count', "$page,$pagesize");
        if ($list) {
            $uids     = array_column($list, 'uid');
            $game_ids = array_column($list, 'game_id');
            $u        = new UserModel();
            $users    = $u->getList(['is_delete' => 0, 'id' => ['in', $uids], 'type' => 2], 'id,nickname,avatar');
            $users    = array_column($users, null, 'id');
            $ua       = new UserAttrModel();
            $attrs    = $ua->getList(['uid' => ['in', $uids]], 'uid,game_id,winning');
            $attr_arr = [];
            foreach ($attrs as $attr) {
                $attr_arr[$attr['uid']][$attr['game_id']] = $attr['winning'];
            }
            $gc     = new GameConfigModel();
            $gclist = $gc->getList(['game_id' => ['in', $game_ids]], 'game_id,para_id,para_str');
            $gcarr  = [];
            foreach ($gclist as $gci) {
                $gcarr[$gci['game_id']][$gci['para_id']] = $gci['para_str'];
            }
            foreach ($list as &$item) {
                if (!empty($users[$item['uid']])) {
                    $item['nickname'] = $users[$item['uid']]['nickname'];
                    $item['avatar']   = $users[$item['uid']]['avatar'];
                } else {
                    $item['nickname'] = '';
                    $item['avatar']   = '';
                }
                if (!empty($gcarr[$item['game_id']]) && !empty($gcarr[$item['game_id']][$item['para_min']])) {
                    $item['para_min_str'] = $gcarr[$item['game_id']][$item['para_min']];
                } else {
                    $item['para_min_str'] = '';
                }
                if (!empty($gcarr[$item['game_id']]) && !empty($gcarr[$item['game_id']][$item['para_max']])) {
                    $item['para_max_str'] = $gcarr[$item['game_id']][$item['para_max']];
                } else {
                    $item['para_max_str'] = '';
                }
                if (!empty($attr_arr[$item['uid']]) && !empty($attr_arr[$item['uid']][$item['game_id']])) {
                    $item['winning'] = $attr_arr[$item['uid']][$item['game_id']];
                } else {
                    $item['winning'] = 0;
                }
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

}
