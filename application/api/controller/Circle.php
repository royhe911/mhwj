<?php
namespace app\api\controller;

use app\common\model\TFriendModel;
use app\common\model\TUserDynamicModel;
use app\common\model\TUserModel;

/**
 * 圈子-控制器
 * @author 贺强
 * @time   2019-01-22 16:21:32
 */
class Circle extends \think\Controller
{
    private $param = [];

    public function __construct()
    {
        $param = file_get_contents('php://input');
        $param = json_decode($param, true);
        if (empty($param['vericode'])) {
            echo json_encode(['status' => 300, 'info' => '非法参数', 'data' => null]);exit;
        }
        $vericode = $param['vericode'];
        unset($param['vericode']);
        $new_code = md5(config('MD5_PARAM'));
        if ($vericode !== $new_code) {
            echo json_encode(['status' => 100, 'info' => '非法参数', 'data' => null]);exit;
        }
        $this->param = $param;
    }

    /**
     * 用户登录
     * @author 贺强
     * @time   2019-01-22 17:58:58
     * @param  TUserModel $u TUserModel 实例
     */
    public function user_login(TUserModel $u)
    {
        $param = $this->param;
        if (empty($param['js_code'])) {
            $msg = ['status' => 1, 'info' => 'js_code 参数不能为空', 'data' => null];
            echo json_encode($msg);exit;
        }
        $js_code = $param['js_code'];
        $appid   = config('APPID');
        $secret  = config('APPSECRET');
        $url     = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$js_code}&grant_type=authorization_code";
        $data    = $this->curl($url);
        $data    = json_decode($data, true);
        if (empty($data['openid'])) {
            echo json_encode(['status' => 2, 'info' => 'code 过期', 'data' => null]);exit;
        }
        $user = $u->getModel(['openid' => $data['openid']]);
        if (!empty($user)) {
            $data['login_time'] = time();
            $data['count']      = $user['count'] + 1;
            // 修改数据
            $id  = $user['id'];
            $res = $u->modify($data, ['id' => $id]);
            if ($res) {
                $msg = ['status' => 0, 'info' => '登录成功', 'data' => ['id' => $user['id']]];
            } else {
                $msg = ['status' => 3, 'info' => '登录失败', 'data' => null];
            }
        } else {
            $data['addtime']    = time();
            $data['login_time'] = time();
            // 添加
            $id = $u->add($data);
            if ($id) {
                $msg = ['status' => 0, 'info' => '登录成功', 'data' => ['id' => $id]];
            } else {
                $msg = ['status' => 4, 'info' => '登录失败', 'data' => null];
            }
        }
        echo json_encode($msg);exit;
    }

    /**
     * 发布动态
     * @author 贺强
     * @time   2019-01-22 16:27:34
     * @param  TUserDynamicModel $ud TUserDynamicModel 实例
     */
    public function release(TUserDynamicModel $ud)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空'];
        } elseif (empty($param['content'])) {
            $msg = ['status' => 3, 'info' => '动态内容不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $u    = new TUserModel();
        $uid  = $param['uid'];
        $user = $u->getModel(['id' => $uid], ['nickname', 'avatar', 'sex']);
        if (!empty($user)) {
            $param['nickname'] = $user['nickname'];
            $param['avatar']   = $user['avatar'];
            $param['sex']      = $user['sex'];
        }
        $param['addtime'] = time();
        // 添加
        $res = $ud->add($param);
        if (!$res) {
            echo json_encode(['status' => 44, 'info' => '发布失败']);exit;
        }
        echo json_encode(['status' => 0, 'info' => '发布成功']);exit;
    }

    /**
     * 获取动态
     * @author 贺强
     * @time   2019-01-22 17:10:57
     * @param  TUserDynamicModel $ud TUserDynamicModel 实例
     */
    public function get_dynamic(TUserDynamicModel $ud)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '登录用户ID不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $where = [];
        $uid   = intval($param['uid']);
        if (!empty($param['is_follow'])) {
            $f     = new TFriendModel();
            $fw    = "(uid1=$uid and is_follow1=1) or (uid2=$uid and is_follow2=1)";
            $users = $f->getList($fw, ['uid1', 'uid2']);
            $uids  = [];
            foreach ($users as $u) {
                if ($uid === $u['uid1']) {
                    $uids[] = $u['uid2'];
                } else {
                    $uids[] = $u['uid1'];
                }
            }
        } elseif (!empty($param['is_circle'])) {
            $u    = new TUserModel();
            $user = $u->getModel(['id' => $uid], ['circle']);
            if (!empty($user) && !empty($user['circle'])) {
                $circle = explode(',', $user['circle']);
                foreach ($circle as $c) {
                    $where .= " or find_in_set('$c',circle)";
                }
                $where = substr($where, 3);
            } else {
                echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => null]);exit;
            }
        } else {
            $where = ['is_open' => 1];
        }
        $page = 1;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        $pagesize = 10;
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $list = $ud->getList($where, ['id', 'zan_count', 'pl_count', 'uid', 'nickname', 'avatar', 'sex', 'content', 'thumb', 'pic', 'addtime'], "$page,$pagesize");
        foreach ($list as &$item) {
            if (!empty($item['addtime'])) {
                $item['addtime'] = date('Y/m/d H:i:s', $item['addtime']);
                if (!empty($item['avatar']) && strpos($item['avatar'], 'http://') === false && strpos($item['avatar'], 'https://') === false) {
                    $item['avatar'] = config('WEBSITE') . $item['avatar'];
                }
                $thumbs = [];
                if (!empty($item['thumb'])) {
                    $thumb = explode(',', $item['thumb']);
                    foreach ($thumb as $t) {
                        if (strpos($t, 'https://') === false && strpos($t, 'http://') === false) {
                            $t = config('WEBSITE') . $t;
                        }
                        $thumbs[] = $t;
                    }
                }
                $item['thumb'] = $thumbs;
                $pics          = [];
                if (!empty($item['pic'])) {
                    $pic = explode(',', $item['pic']);
                    foreach ($pic as $p) {
                        if (strpos($p, 'https://') === false && strpos($p, 'http://') === false) {
                            $p = config('WEBSITE') . $p;
                        }
                        $pics[] = $p;
                    }
                }
                $item['pic'] = $pics;
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }
}
