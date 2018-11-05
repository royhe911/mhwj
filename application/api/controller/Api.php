<?php
namespace app\api\controller;

use app\common\model\FeedbackModel;
use app\common\model\GameConfigModel;
use app\common\model\GameModel;
use app\common\model\MessageModel;
use app\common\model\NoticeModel;
use app\common\model\UserAttrModel;
use app\common\model\UserModel;
use Qcloud\Sms\SmsSingleSender;
use think\Session;

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
        if (!empty($this->param['count'])) {
            $count = $this->param['count'];
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
        if (!empty($this->param['count'])) {
            $count = $this->param['count'];
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
        if (empty($this->param['js_code'])) {
            echo json_encode(['status' => 1, 'info' => 'js_code 参数不能为空', 'data' => null]);exit;
        }
        $js_code = $this->param['js_code'];
        $appid   = 'wxe6f37de8e1e3225e';
        $secret  = '357566bea005201ce062acaabd4a58e9';
        if (!empty($this->param['type']) && intval($this->param['type']) === 2) {
            $appid  = 'wxecd6bfdba0623aa5';
            $secret = '8ff39ccfde133942cd8933b240a79960';
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
            $data['updatetime'] = time();
            $data['count']      = $user['count'] + 1;
            $res                = $u->modify($data, ['id' => $user['id']]);
            if ($res) {
                $msg = ['status' => 0, 'info' => '登录成功', 'data' => ['id' => $user['id']]];
            } else {
                $msg = ['status' => 3, 'info' => '登录失败', 'data' => null];
            }
        } else {
            $data['type']       = 1;
            $data['addtime']    = time();
            $data['login_time'] = time();
            $id                 = $u->add($data);
            if ($id) {
                $msg = ['status' => 0, 'info' => '登录成功', 'data' => ['id' => $id]];
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
        if (empty($this->param['id'])) {
            echo json_encode(['status' => 1, 'info' => '参数缺失', 'data' => null]);exit;
        }
        $this->param['updatetime'] = time();
        // 修改信息
        $res = $u->modify($this->param, ['id' => $this->param['id']]);
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
        if (empty($this->param['id'])) {
            echo json_encode(['status' => 1, 'info' => '参数缺失', 'data' => null]);exit;
        }
        if (empty($this->param['mobile'])) {
            echo json_encode(['status' => 8, 'info' => '手机号不能为空', 'data' => null]);exit;
        }
        $mobile = $this->param['mobile'];
        if (empty($this->param['code'])) {
            echo json_encode(['status' => 9, 'info' => '验证码不能为空', 'data' => null]);exit;
        }
        $code = $this->param['code'];
        $msg  = [];
        if (empty(session('v_' . $mobile))) {
            $msg = ['status' => 2, 'info' => '验证码过期', 'data' => null];
        } elseif (session('v_' . $mobile) !== $code) {
            $msg = ['status' => 3, 'info' => '验证码错误', 'data' => null];
        } else {
            session('v_' . $mobile, null);
            $msg = ['status' => 0, 'info' => '验证成功', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        if (empty($this->param['avatar'])) {
            echo json_encode(['status' => 2, 'info' => '头像不能为空', 'data' => null]);exit;
        }
        if (empty($this->param['nickname'])) {
            echo json_encode(['status' => 3, 'info' => '昵称不能为空', 'data' => null]);exit;
        }
        if (empty($this->param['sex'])) {
            echo json_encode(['status' => 4, 'info' => '性别不能为空', 'data' => null]);exit;
        }
        if (empty($this->param['birthday'])) {
            echo json_encode(['status' => 5, 'info' => '生日不能为空', 'data' => null]);exit;
        }
        if (empty($this->param['introduce'])) {
            echo json_encode(['status' => 6, 'info' => '简介不能为空', 'data' => null]);exit;
        }
        if (empty($this->param['tape'])) {
            echo json_encode(['status' => 7, 'info' => '录音地址不能为空', 'data' => null]);exit;
        }
        $this->param['updatetime'] = time();
        $this->param['status']     = 1;
        // 修改信息
        $res = $u->modify($this->param, ['id' => $this->param['id']]);
        if ($res !== false) {
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
        if (empty($this->param['id'])) {
            echo json_encode(['status' => 1, 'info' => '参数缺失']);exit;
        }
        $user = $u->getModel(['id' => $this->param['id']], 'id,nickname,`type`,avatar,contribution');
        if ($user) {
            $msg = ['status' => 0, 'info' => '获取成功', 'data' => $user];
        } else {
            $msg = ['status' => 4, 'info' => '获取失败', 'data' => null];
        }
        echo json_encode($msg);exit;
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
        if (!empty($this->param['page'])) {
            $page = $this->param['page'];
        }
        if (!empty($this->param['pagesize'])) {
            $pagesize = $this->param['pagesize'];
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
        if (empty($this->param['uid']) || empty($this->param['game_id'])) {
            echo json_encode(['status' => 1, 'info' => '参数缺失', 'data' => null]);exit;
        }
        if (empty($this->param['curr_para'])) {
            echo json_encode(['status' => 2, 'info' => '当前段位不能为空', 'data' => null]);exit;
        }
        if (empty($this->param['play_para'])) {
            echo json_encode(['status' => 3, 'info' => '陪玩段位不能为空', 'data' => null]);exit;
        }
        if (empty($this->param['play_type'])) {
            echo json_encode(['status' => 4, 'info' => '陪玩类型不能为空', 'data' => null]);exit;
        }
        if (empty($this->param['level_url'])) {
            echo json_encode(['status' => 5, 'info' => '水平截图不能为空', 'data' => null]);exit;
        }
        $userAttr = $ua->getModel(['uid' => $this->param['uid'], 'game_id' => $this->param['game_id']]);
        if ($userAttr) {
            $res = $ua->modify($this->param, ['uid' => $this->param['uid'], 'game_id' => $this->param['game_id']]);
        } else {
            $res = $ua->add($this->param);
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
        if (empty($this->param['uid'])) {
            echo json_encode(['status' => 1, 'info' => '用户ID不能为空', 'data' => null]);exit;
        }
        $uid  = $this->param['uid'];
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
        if (empty($this->param['game_id'])) {
            echo json_encode(['status' => 1, 'info' => '游戏ID不能为空', 'data' => null]);exit;
        }
        $game_id = $this->param['game_id'];
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
        if (empty($this->param['id'])) {
            echo json_encode(['status' => 1, 'info' => '参数缺失', 'data' => null]);exit;
        }
        $id = $this->param['id'];
        if (!preg_match('/^\d+$/', $id)) {
            echo json_encode(['status' => 2, 'info' => '非法参数', 'data' => null]);exit;
        }
        // 分页参数
        $page     = 1;
        $pagesize = 100;
        if (!empty($this->param['page'])) {
            $page = $this->param['page'];
        }
        if (!empty($this->param['pagesize'])) {
            $pagesize = $this->param['pagesize'];
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
        if (empty($this->param['uid'])) {
            echo json_encode(['status' => 1, 'info' => '反馈用户ID不能为空', 'data' => null]);exit;
        }$uid = $this->param['uid'];
        if (empty(ltrim(rtrim($this->param['content'])))) {
            echo json_encode(['status' => 2, 'info' => '反馈内容不能为空', 'data' => null]);exit;
        }
        $this->param['addtime'] = time();
        $res                    = $f->add($this->param);
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
        if (empty($this->param['mobile'])) {
            echo json_encode(['status' => 1, 'info' => '手机号不能为空', 'data' => null]);exit;
        }
        $mobile = $this->param['mobile'];
        session('v_' . $mobile, null);
        $num = 4;
        if (!empty($this->param['num'])) {
            $num = intval($this->param['num']);
        }
        $vericode   = get_random_num($num);
        $sms        = new SmsSingleSender(config('SDKAPPID'), config('APPKEY'));
        $templateId = 221888;
        $param      = [$vericode];
        $smsSign    = '';
        $res        = $sms->sendWithParam('86', $mobile, $templateId, $param, $smsSign, '', '');
        $res        = json_decode($res, true);
        if ($res['result'] === 0) {
            session('v_' . $mobile, $vericode);
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
    public function check_vericode()
    {
        if (empty($this->param['code']) || empty($this->param['mobile'])) {
            echo json_encode(['status' => 1, 'info' => '非法参数', 'data' => null]);exit;
        }
        $mobile = $this->param['mobile'];
        if (empty(session('v_' . $mobile))) {
            $msg = ['status' => 2, 'info' => '验证码过期', 'data' => null];
        } elseif (session('v_' . $mobile) !== $this->param['code']) {
            $msg = ['status' => 3, 'info' => '验证码错误', 'data' => null];
        } else {
            session('v_' . $mobile, null);
            $msg = ['status' => 0, 'info' => '验证成功', 'data' => null];
        }
        echo json_encode($msg);exit;
    }

}
