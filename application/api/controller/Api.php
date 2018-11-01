<?php
namespace app\api\controller;

use app\common\model\GameConfigModel;
use app\common\model\GameModel;
use app\common\model\NoticeModel;
use app\common\model\UserAttrModel;
use app\common\model\UserModel;

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
        $where = ['is_delete' => 0, 'status' => 0];
        $list  = $n->getList($where, '`name`,`url`', "1,$count", "sort");
        if (!empty($list)) {
            foreach ($list as &$item) {
                if (!empty($item['url'])) {
                    $item['url'] = config('WEBSITE') . $item['url'];
                }
            }
            echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);
        } else {
            echo json_encode(['status' => 4, 'info' => '暂无数据', 'data' => null]);
        }
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
        $url     = "https://api.weixin.qq.com/sns/jscode2session?appid=wxe6f37de8e1e3225e&secret=357566bea005201ce062acaabd4a58e9&js_code={$js_code}&grant_type=authorization_code";
        $data    = $this->curl($url);
        $data    = json_decode($data, true);
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
     * 用户升级审核
     * @author 贺强
     * @time   2018-11-01 14:31:17
     * @param  UserModel $u UserModel 实例
     */
    public function user_examine(UserModel $u)
    {
        if (empty($this->param['id'])) {
            echo json_encode(['status' => 1, 'info' => '参数缺失', 'data' => null]);exit;
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
            $msg = ['status' => 4, 'info' => '提交失败', 'data' => null];
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
        $userAttr = $ua->getModel(['uid' => $this->param['uid'], 'game_id' => $this->param['game_id']]);
        if ($userAttr) {
            $res = $ua->modify($this->param, ['uid' => $this->param['uid'], 'game_id' => $this->param['game_id']]);
        } else {
            $res = $ua->add($this->param);
        }
        if ($res) {
            $msg = ['status' => 0, 'info' => '添加成功', 'data' => null];
        } else {
            $msg = ['status' => 4, 'info' => '添加失败', 'data' => null];
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

}
