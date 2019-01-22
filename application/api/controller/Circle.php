<?php
namespace app\api\controller;

use app\common\model\TFriendModel;
use app\common\model\TPraiseModel;
use app\common\model\TUserDynamicCommentModel;
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
            echo json_encode(['status' => 300, 'info' => '非法参数']);exit;
        }
        $vericode = $param['vericode'];
        unset($param['vericode']);
        $new_code = md5(config('MD5_PARAM'));
        if ($vericode !== $new_code) {
            echo json_encode(['status' => 100, 'info' => '非法参数']);exit;
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
            $msg = ['status' => 1, 'info' => 'js_code 参数不能为空'];
            echo json_encode($msg);exit;
        }
        $js_code = $param['js_code'];
        $appid   = config('APPID');
        $secret  = config('APPSECRET');
        $url     = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$js_code}&grant_type=authorization_code";
        $data    = $this->curl($url);
        $data    = json_decode($data, true);
        if (empty($data['openid'])) {
            echo json_encode(['status' => 2, 'info' => 'code 过期']);exit;
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
                $msg = ['status' => 3, 'info' => '登录失败'];
            }
        } else {
            $data['addtime']    = time();
            $data['login_time'] = time();
            // 添加
            $id = $u->add($data);
            if ($id) {
                $msg = ['status' => 0, 'info' => '登录成功', 'data' => ['id' => $id]];
            } else {
                $msg = ['status' => 4, 'info' => '登录失败'];
            }
        }
        echo json_encode($msg);exit;
    }

    /**
     * 同步用户信息
     * @author 贺强
     * @time   2019-01-22 18:46:13
     * @param  TUserModel $u TUserModel 实例
     */
    public function sync_userinfo(TUserModel $u)
    {
        $param = $this->param;
        if (empty($param['id'])) {
            echo json_encode(['status' => 1, 'info' => '参数缺失']);exit;
        }
        $res = $u->modify($param, ['id' => $param['id']]);
        if ($res !== false) {
            $msg = ['status' => 0, 'info' => '同步成功'];
        } else {
            $msg = ['status' => 4, 'info' => '同步失败'];
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
        } elseif (empty($param['type'])) {
            $msg = ['status' => 3, 'info' => '要获取的数据类型不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $where = [];
        $uid   = intval($param['uid']);
        $type  = intval($param['type']);
        if ($type === 1) {
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
            $uids  = array_merge($uids, [$uid]);
            $where = ['uid' => ['in', $uids]];
        } elseif ($type === 2) {
            $where = "is_open=1 or uid=$uid";
        } elseif ($type === 3) {
            $u    = new TUserModel();
            $user = $u->getModel(['id' => $uid], ['circle']);
            if (!empty($user) && !empty($user['circle'])) {
                $circle = explode(',', $user['circle']);
                foreach ($circle as $c) {
                    $where .= " or find_in_set('$c',circle)";
                }
                $where = substr($where, 3);
                $where .= " or uid=$uid";
            } else {
                echo json_encode(['status' => 0, 'info' => '获取成功']);exit;
            }
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

    /**
     * 点赞
     * @author 贺强
     * @time   2019-01-22 19:44:34
     * @param  TPraiseModel $p TPraiseModel 实例
     */
    public function zan(TPraiseModel $p)
    {
        $param = $this->param;
        if (empty($param['obj_id'])) {
            $msg = ['status' => 1, 'info' => '动态或评论ID不能为空'];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 3, 'info' => '用户ID不能为空'];
        } elseif (empty($param['type'])) {
            $msg = ['status' => 5, 'info' => '类型不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $param['addtime'] = time();
        // 添加
        $res = $p->do_zan($param);
        if ($res !== true) {
            echo json_encode(['status' => $res, 'info' => '失败']);exit;
        }
        echo json_encode(['status' => 0, 'info' => '成功']);exit;
    }

    public function comment(TUserDynamicCommentModel $fc)
    {
        $param = $this->param;
        if (empty($param['obj_id'])) {
            $msg = ['status' => 1, 'info' => '心情或评论ID不能为空', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 3, 'info' => '评论者ID不能为空', 'data' => null];
        } elseif (empty($param['content'])) {
            $msg = ['status' => 5, 'info' => '评论内容不能为空', 'data' => null];
        } elseif (empty($param['type'])) {
            $msg = ['status' => 7, 'info' => '消息类型不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $param['addtime'] = time();
        // 评论
        $res = $fc->do_comment($param);
        if ($res !== true) {
            echo json_encode(['status' => $res, 'info' => '评论失败', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '评论成功', 'data' => null]);exit;
    }
}