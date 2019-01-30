<?php
namespace app\api\controller;

use app\common\model\TChatModel;
use app\common\model\TDynamicCommentModel;
use app\common\model\TDynamicModel;
use app\common\model\TFriendModel;
use app\common\model\TGameModel;
use app\common\model\TPraiseModel;
use app\common\model\TRoomModel;
use app\common\model\TSchoolModel;
use app\common\model\TTopicModel;
use app\common\model\TUserGameModel;
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
            // 判断是否需要完善资料
            $perfect = 1;
            if (empty($user['school']) || empty($user['department']) || empty($user['grade'])) {
                $perfect = 0;
            }
            // 修改数据
            $msg = ['status' => 0, 'info' => '登录成功', 'data' => ['id' => $user['id'], 'perfect' => $perfect]];
        } else {
            $data['addtime'] = time();
            // 添加
            $id = $u->add($data);
            if ($id) {
                $msg = ['status' => 0, 'info' => '登录成功', 'data' => ['id' => $id, 'perfect' => 0]];
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
        if (!empty($param['gid']) && empty($param['online'])) {
            echo json_encode(['status' => 3, 'info' => '在线时间段不能为空']);exit;
        }
        if (!empty($param['school'])) {
            $s      = new TSchoolModel();
            $school = $s->getModel(['name' => $param['school']], ['id']);
            if (!empty($school)) {
                $param['circle'] = $school['id'];
            }
        }
        $res = $u->syncinfo($param);
        if ($res !== true) {
            echo json_encode(['status' => $res, 'info' => '同步失败']);exit;
        }
        echo json_encode(['status' => 0, 'info' => '同步成功']);exit;
    }

    /**
     * 发布动态
     * @author 贺强
     * @time   2019-01-22 16:27:34
     * @param  TDynamicModel $d TDynamicModel 实例
     */
    public function release(TDynamicModel $ud)
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
        $user = $u->getModel(['id' => $uid], ['nickname', 'avatar', 'sex', 'circle', 'status']);
        if (!empty($user)) {
            if ($user['status'] === 44) {
                echo json_encode(['status' => 5, 'info' => '您已被禁用，暂时不能发布']);exit;
            }
            $param['nickname'] = $user['nickname'];
            $param['avatar']   = $user['avatar'];
            $param['sex']      = $user['sex'];
        }
        if (empty($user['circle'])) {
            $param['is_open'] = 1;
        }
        $param['addtime'] = time();
        // 添加
        $res = $ud->add($param);
        if (!$res) {
            echo json_encode(['status' => 44, 'info' => '发布失败']);exit;
        }
        $u->increment('count', ['id' => $uid]);
        if (!empty($param['topic'])) {
            $t = new TTopicModel();
            $t->increment('count', ['id' => ['in', $param['topic']]]);
        }
        echo json_encode(['status' => 0, 'info' => '发布成功']);exit;
    }

    /**
     * 获取动态
     * @author 贺强
     * @time   2019-01-22 17:10:57
     * @param  TDynamicModel $ud TDynamicModel 实例
     */
    public function get_dynamic(TDynamicModel $ud)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '登录用户ID不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $where = '1';
        $uid   = intval($param['uid']);
        $type  = 0;
        if (!empty($param['type'])) {
            $type = intval($param['type']);
        }
        // 获取未知的评论数量
        $dc    = new TDynamicCommentModel();
        $count = $dc->getCount(['userid' => $uid, 'is_tip' => 0]);
        if ($type === 1) {
            $f     = new TFriendModel();
            $fw    = "(uid1=$uid and follow1=1) or (uid2=$uid and follow2=1)";
            $users = $f->getList($fw, ['uid1', 'uid2']);
            $uids  = '0';
            foreach ($users as $u) {
                if ($uid === $u['uid1']) {
                    $uids .= ",{$u['uid2']}";
                } else {
                    $uids .= ",{$u['uid1']}";
                }
            }
            $where .= " and uid in ($uids)";
        } elseif ($type === 2) {
            $where .= " and (is_open=1 or uid=$uid)";
        } elseif ($type === 3) {
            $u    = new TUserModel();
            $user = $u->getModel(['id' => $uid], ['circle']);
            if (!empty($user) && !empty($user['circle'])) {
                $circle = explode(',', $user['circle']);
                $wherec = '1';
                foreach ($circle as $c) {
                    $wherec .= " or find_in_set('$c',circle)";
                }
                $ids = $u->getList($wherec, ['id']);
                $ids = array_column($ids, 'id');
                $ids = implode(',', $ids);
                $where .= " and uid in ($ids)";
            } else {
                echo json_encode(['status' => 0, 'info' => '获取成功']);exit;
            }
        }
        if (!empty($param['topic'])) {
            $where .= " and find_in_set('{$param['topic']}',topic)";
        }
        if (!empty($param['is_tip'])) {
            $dcs = $dc->getList(['userid' => $uid, 'is_tip' => 0], ['id', 'did']);
            if (!empty($dcs)) {
                $ids = array_column($dcs, 'did');
                $ids = implode(',', $ids);
                $where .= " and id in ($ids)";
            } else {
                echo json_encode(['status' => 0, 'info' => '暂无数据']);exit;
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
        $order = '';
        if (!empty($param['heat'])) {
            $order = 'count desc,';
        }
        $list = $ud->getList($where, ['id', 'zan_count', 'pl_count', 'uid', 'nickname', 'avatar', 'sex', 'sort', 'is_recommend', 'origin', 'is_open', 'topic', 'content', 'thumb', 'type', 'pic', 'addtime', 'sum(zan_count+pl_count) count'], "$page,$pagesize", 'is_recommend desc,' . $order . 'sort,addtime desc', 'id');
        if ($list) {
            // 获取我的关注
            $fnds = $this->friends(['uid' => $uid, 'type' => 1]);
            $fnds = array_column($fnds, 'nickname', 'uid');
            // 获取我的点赞
            $p    = new TPraiseModel();
            $zans = $p->getList(['uid' => $uid, 'type' => 1], ['obj_id', 'uid']);
            $zans = array_column($zans, 'uid', 'obj_id');
            // 获取动态话题
            $ft    = new TTopicModel();
            $topic = $ft->getList([], ['id', 'title']);
            $topic = array_column($topic, 'title', 'id');
            foreach ($list as &$item) {
                if ($item['uid'] === $uid) {
                    $item['is_del'] = 1;
                } else {
                    $item['is_del'] = 0;
                }
                // 动态话题
                $tps = [];
                if (!empty($item['topic'])) {
                    $topics = explode(',', $item['topic']);
                    foreach ($topics as $t) {
                        if (!empty($topic[$t])) {
                            $tps[] = ['id' => $t, 'title' => $topic[$t]];
                        }
                    }
                }
                $item['topic'] = $tps;
                // 当前用户是否关注发布者
                if (!empty($fnds[$item['uid']])) {
                    $item['is_follow'] = 1;
                } else {
                    $item['is_follow'] = 0;
                }
                // 当前用户是否赞过此动态
                if (!empty($zans[$item['id']])) {
                    $item['is_zan'] = 1;
                } else {
                    $item['is_zan'] = 0;
                }
                // 显示发布时间
                $diff = time() - $item['addtime'];
                if ($diff < 60) {
                    $item['addtime'] = '刚刚';
                } elseif ($diff < 3600) {
                    $item['addtime'] = intval($diff / 60) . '分钟前';
                } elseif ($diff < 86400) {
                    $item['addtime'] = intval($diff / 3600) . '小时前';
                } else {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
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
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list, 'count' => $count]);exit;
    }

    /**
     * 获取我的关注/粉丝/朋友
     * @author 贺强
     * @time   2019-01-23 19:01:22
     * @param  array  $data 参数
     */
    public function friends($data = [])
    {
        $param = $this->param;
        if ($data) {
            $param = $data;
        }
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空'];
        } elseif (empty($param['type'])) {
            $msg = ['status' => 3, 'info' => '类型不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid  = intval($param['uid']);
        $type = intval($param['type']);
        switch ($type) {
            case 1:
                $where = "(uid1=$uid and follow1=1) or (uid2=$uid and follow2=1)";
                break;
            case 2:
                $where = "(uid1=$uid and follow2=1) or (uid2=$uid and follow1=1)";
                break;
            default:
                $where = ['uid1|uid2' => $uid, 'is_friend' => 1];
                break;
        }
        $f = new TFriendModel();
        if (!empty($param['is_count'])) {
            $count = $f->getCount($where);
            if ($data) {
                return $count;
            }
            echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $count]);
        } else {
            $page = 1;
            if (!empty($param['page'])) {
                $page = $param['page'];
            }
            $pagesize = 10;
            if (!empty($param['pagesize'])) {
                $pagesize = $param['pagesize'];
            }
            $list = $f->getList($where, ['uid1', 'nickname1', 'avatar1', 'sex1', 'uid2', 'nickname2', 'avatar2', 'sex2', 'is_friend'], "$page,$pagesize");
            foreach ($list as &$item) {
                // 过滤掉本人只取好友信息
                if ($item['uid1'] === $uid) {
                    unset($item['uid1'], $item['nickname1'], $item['avatar1'], $item['sex1']);
                    $item['uid']      = $item['uid2'];
                    $item['nickname'] = $item['nickname2'];
                    $item['avatar']   = $item['avatar2'];
                    $item['sex']      = $item['sex2'];
                    unset($item['uid2'], $item['nickname2'], $item['avatar2'], $item['sex2']);
                } elseif ($item['uid2'] === $uid) {
                    unset($item['uid2'], $item['nickname2'], $item['avatar2'], $item['sex2']);
                    $item['uid']      = $item['uid1'];
                    $item['nickname'] = $item['nickname1'];
                    $item['avatar']   = $item['avatar1'];
                    $item['sex']      = $item['sex1'];
                    unset($item['uid1'], $item['nickname1'], $item['avatar1'], $item['sex1']);
                }
            }
            if ($data) {
                return $list;
            }
            echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
        }
    }

    /**
     * 获取通讯录
     * @author 贺强
     * @time   2019-01-24 16:03:49]
     */
    public function maillist()
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空'];
        } elseif (empty($param['type'])) {
            $msg = ['status' => 3, 'info' => '类型不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid  = intval($param['uid']);
        $type = intval($param['type']);
        switch ($type) {
            case 1:
                $where = "(uid1=$uid and follow1=1) or (uid2=$uid and follow2=1)";
                break;
            case 2:
                $where = "(uid1=$uid and follow2=1) or (uid2=$uid and follow1=1)";
                break;
        }
        $f = new TFriendModel();
        if (!empty($param['is_count'])) {
            $count = $f->getCount($where);
            echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $count]);
        } else {
            $page = 1;
            if (!empty($param['page'])) {
                $page = $param['page'];
            }
            $pagesize = 10;
            if (!empty($param['pagesize'])) {
                $pagesize = $param['pagesize'];
            }
            $list = $f->getList($where, ['uid1', 'nickname1', 'avatar1', 'sex1', 'uid2', 'nickname2', 'avatar2', 'sex2', 'is_friend'], "$page,$pagesize");
            $data = [];
            foreach ($list as $item) {
                // 过滤掉本人只取好友信息
                if ($item['uid1'] === $uid) {
                    $data[] = ['uid' => $item['uid2'], 'nickname' => $item['nickname2'], 'avatar' => $item['avatar2'], 'sex' => $item['sex2'], 'is_friend' => $item['is_friend'], 'isTouchMove' => false];
                } else {
                    $data[] = ['uid' => $item['uid1'], 'nickname' => $item['nickname1'], 'avatar' => $item['avatar1'], 'sex' => $item['sex1'], 'is_friend' => $item['is_friend'], 'isTouchMove' => false];
                }
            }
            $result = [];
            foreach ($data as $item) {
                $first = get_first($item['nickname']);
                if (!$first) {
                    $first = '#';
                }
                // 拼装数组
                $result[$first][] = $item;
            }
            ksort($result);
            reset($result);
            $key = key($result);
            if ($key === '#') {
                // 删除第一个元素并返回
                $r = array_shift($result);
                // 把第一个元素再赋值给原数组
                $result['#'] = $r;
            }
            // 重新组合数组
            $arr = [];
            $i   = 1;
            foreach ($result as $k => $rst) {
                $arr[] = ['id' => $i, 'region' => strtoupper($k), 'items' => $rst];
                $i++;
            }
            echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $arr]);exit;
        }
    }

    /**
     * 汉字转拼音
     */
    public function zh_to_py()
    {
        $param   = $this->param;
        $chinese = $this->param['chinese'];
        $type    = 0;
        if (!empty($param['type'])) {
            $type = intval($param['type']);
        }
        switch ($type) {
            case 1:
                $res = get_first($chinese);
                break;
            case 2:
                $res = utf8_to($chinese, true);
                break;
            default:
                $res = utf8_to($chinese);
                break;
        }
        echo $res;exit;
    }

    /**
     * 关注提醒
     * @author 贺强
     * @time   2019-01-24 11:08:25
     * @param  TFriendModel $f TFriendModel 实例
     */
    public function follow_tip(TFriendModel $f)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid   = $param['uid'];
        $where = "(uid1=$uid and follow2=1 and tip2=0) or (uid2=$uid and follow1=1 and tip1=0)";
        $count = $f->getCount($where);
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $count]);exit;
    }

    /**
     * 修改关注提醒
     * @author 贺强
     * @time   2019-01-24 11:17:00
     * @param  TFriendModel $f TFriendModel 实例
     */
    public function modifytip(TFriendModel $f)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid = $param['uid'];
        $f->modifyField('tip1', 1, ['uid2' => $uid, 'follow1' => 1]);
        $f->modifyField('tip2', 1, ['uid1' => $uid, 'follow2' => 1]);
        echo json_encode(['status' => 0, 'info' => '修改成功']);exit;
    }

    /**
     * 关注/取消关注
     * @author 贺强
     * @time   2019-01-23 10:44:08
     * @param  TFriendModel $f TFriendModel 实例
     */
    public function follow(TFriendModel $f)
    {
        $param = $this->param;
        if (empty($param['uid1'])) {
            $msg = ['status' => 1, 'info' => '关注者ID不能为空'];
        } elseif (empty($param['uid2'])) {
            $msg = ['status' => 3, 'info' => '被关注者ID不能为空'];
        } elseif ($param['uid1'] === $param['uid2']) {
            $msg = ['status' => 5, 'info' => '不能关注自己'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $u     = new TUserModel();
        $uid1  = intval($param['uid1']);
        $uid2  = intval($param['uid2']);
        $where = "(uid1=$uid1 and uid2=$uid2) or (uid1=$uid2 and uid2=$uid1)";
        $fnd   = $f->getModel($where);
        if (empty($fnd)) {
            $users = $u->getList(['id' => ['in', [$uid1, $uid2]]], ['id', 'nickname', 'avatar', 'sex']);
            foreach ($users as $user) {
                if ($user['id'] === $uid1) {
                    $param['nickname1'] = $user['nickname'];
                    $param['avatar1']   = $user['avatar'];
                    $param['sex1']      = $user['sex'];
                } elseif ($user['id'] === $uid2) {
                    $param['nickname2'] = $user['nickname'];
                    $param['avatar2']   = $user['avatar'];
                    $param['sex2']      = $user['sex'];
                }
            }
            $param['follow1'] = 1;
            // 添加
            $res = $f->add($param);
        } else {
            $id    = $fnd['id'];
            $where = ['id' => $id];
            if ($fnd['uid1'] === $uid1) {
                // 如果之前已关注
                if ($fnd['follow1']) {
                    // 如果对方已经关注了他，则取消关注
                    if ($fnd['follow2']) {
                        $data = ['follow1' => 0, 'is_friend' => 0, 'friend_time' => 0];
                        $res  = $f->modify($data, $where);
                    } else {
                        // 如果对方没有关注，则删除此数据
                        $res = $f->delById($id);
                    }
                } else {
                    // 如果之前没关注则关注对方并成为好友
                    $data = ['follow1' => 1, 'is_friend' => 1, 'friend_time' => time()];
                    $res  = $f->modify($data, $where);
                }
            } elseif ($fnd['uid2'] === $uid1) {
                // 如果之前已关注
                if ($fnd['follow2']) {
                    // 如果对方已经关注了他，则取消关注
                    if ($fnd['follow1']) {
                        $data = ['follow2' => 0, 'is_friend' => 0, 'friend_time' => 0];
                        $res  = $f->modify($data, $where);
                    } else {
                        // 如果对方没有关注，则删除此数据
                        $res = $f->delById($id);
                    }
                } else {
                    // 如果之前没关注则关注对方并成为好友
                    $data = ['follow2' => 1, 'is_friend' => 1, 'friend_time' => time()];
                    $res  = $f->modify($data, $where);
                }
            }
            $is_friend = 0;
            if (!empty($data) && $data['is_friend'] === 1) {
                $is_friend = 1;
            }
            $r = new TRoomModel();
            $r->modifyField('is_friend', $is_friend, "(uid1=$uid1 and uid2=$uid2) or (uid1=$uid2 and uid2=$uid1)");
        }
        if (!$res) {
            echo json_encode(['status' => 40, 'info' => '关注失败']);exit;
        }
        echo json_encode(['status' => 0, 'info' => '成功']);exit;
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

    /**
     * 评论/回复
     * @author 贺强
     * @time   2019-01-22 20:13:44
     * @param  TDynamicCommentModel $dc TDynamicCommentModel 实例
     */
    public function comment(TDynamicCommentModel $dc)
    {
        $param = $this->param;
        if (empty($param['obj_id'])) {
            $msg = ['status' => 1, 'info' => '动态或评论ID不能为空'];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 3, 'info' => '评论者ID不能为空'];
        } elseif (empty($param['content'])) {
            $msg = ['status' => 5, 'info' => '评论内容不能为空'];
        } elseif (empty($param['type'])) {
            $msg = ['status' => 7, 'info' => '消息类型不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $obj_id = $param['obj_id'];
        $type   = intval($param['type']);
        if ($type === 1) {
            $d  = new TDynamicModel();
            $dy = $d->getModel(['id' => $obj_id], ['uid']);
            if (!empty($dy)) {
                $param['userid'] = $dy['uid'];
            }
        }
        if ($type === 2) {
            $comm = $dc->getModel(['id' => $obj_id], ['uid', 'nickname']);
            if (!empty($comm)) {
                $param['userid']    = $comm['uid'];
                $param['ruid']      = $comm['uid'];
                $param['rnickname'] = $comm['nickname'];
            } else {
                echo json_encode(['status' => 2, 'info' => '回复的评论被删除']);exit;
            }
        }
        $param['addtime'] = time();
        // 评论
        $res = $dc->do_comment($param);
        if ($res !== true) {
            echo json_encode(['status' => $res, 'info' => '评论失败']);exit;
        }
        echo json_encode(['status' => 0, 'info' => '评论成功']);exit;
    }

    /**
     * 动态详情
     * @author 贺强
     * @time   2019-01-23 09:12:41
     * @param  TDynamicModel $d TDynamicModel 实例
     */
    public function dynamic_info(TDynamicModel $d)
    {
        $param = $this->param;
        if (empty($param['did'])) {
            $msg = ['status' => 1, 'info' => '动态ID不能为空'];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 3, 'info' => '用户ID不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid     = intval($param['uid']);
        $did     = intval($param['did']);
        $dynamic = $d->getModel(['id' => $did]);
        if ($dynamic) {
            $t     = new TTopicModel();
            $topic = $t->getList([], ['id', 'title']);
            $topic = array_column($topic, 'title', 'id');
            $tps   = [];
            if (!empty($dynamic['topic'])) {
                $topics = explode(',', $dynamic['topic']);
                foreach ($topics as $t) {
                    $tps[] = ['id' => $t, 'title' => $topic[$t]];
                }
            }
            $dynamic['topic'] = $tps;
            // 获取当前用户是否关注此用户
            $f     = new TFriendModel();
            $where = "(uid1=$uid and follow1=1 and uid2={$dynamic['uid']}) or (uid2=$uid and follow2=1 and uid1={$dynamic['uid']})";
            $count = $f->getCount($where);
            if ($count) {
                $dynamic['is_follow'] = 1;
            } else {
                $dynamic['is_follow'] = 0;
            }
            $thumbs = [];
            if (!empty($dynamic['thumb'])) {
                $thumbs = explode(',', $dynamic['thumb']);
                foreach ($thumbs as &$thumb) {
                    if (strpos($thumb, 'http://') === false && strpos($thumb, 'https://') === false) {
                        $thumb = config('WEBSITE') . $thumb;
                    }
                }
            }
            $dynamic['thumb'] = $thumbs;
            $pics             = [];
            if (!empty($dynamic['pic'])) {
                $pics = explode(',', $dynamic['pic']);
                foreach ($pics as &$pic) {
                    if (strpos($pic, 'http://') === false && strpos($pic, 'https://') === false) {
                        $pic = config('WEBSITE') . $pic;
                    }
                }
            }
            $dynamic['pic'] = $pics;
            $zan_arr        = [];
            // 取得当前用户赞过的
            $p    = new TPraiseModel();
            $zans = $p->getList(['uid' => $uid], ['obj_id', 'type', 'uid']);
            foreach ($zans as $z) {
                $zan_arr[$z['type'] . '_' . $z['obj_id']] = $z;
            }
            if (!empty($zan_arr["2_{$dynamic['id']}"])) {
                $dynamic['is_zan'] = 1;
            } else {
                $dynamic['is_zan'] = 0;
            }
            // 显示发布时间
            $diff = time() - $dynamic['addtime'];
            if ($diff < 60) {
                $dynamic['addtime'] = $diff . '秒前';
            } elseif ($diff < 3600) {
                $dynamic['addtime'] = intval($diff / 60) . '分钟前';
            } elseif ($diff < 86400) {
                $dynamic['addtime'] = intval($diff / 3600) . '小时前';
            } else {
                $dynamic['addtime'] = date('Y-m-d H:i:s', $dynamic['addtime']);
            }
            if ($dynamic['uid'] === $uid) {
                $dynamic['is_del'] = 1;
            } else {
                $dynamic['is_del'] = 0;
            }
            $dc   = new TDynamicCommentModel();
            $list = $dc->getList(['did' => $did, 'type' => 1], ['id', 'uid', 'nickname', 'avatar', 'sex', 'content', 'zan_count', 'addtime'], null, 'addtime desc');
            if ($list) {
                $cos = $dc->getList(['did' => $did, 'type' => 2, 'cid' => ['<>', 0]], ['id', 'cid', 'uid', 'nickname', 'sex', 'content', 'addtime', 'ruid', 'rnickname']);
                $car = [];
                foreach ($cos as $cs) {
                    $diff2 = time() - $cs['addtime'];
                    if ($diff2 < 60) {
                        $cs['addtime'] = '刚刚';
                    } elseif ($diff2 < 3600) {
                        $cs['addtime'] = intval($diff2 / 60) . '分钟前';
                    } elseif ($diff2 < 86400) {
                        $cs['addtime'] = intval($diff2 / 3600) . '小时前';
                    } else {
                        $cs['addtime'] = date('Y-m-d H:i:s', $cs['addtime']);
                    }
                    // 判断是否可以删除回复
                    if ($cs['uid'] === $uid || $dynamic['is_del']) {
                        $cs['is_del'] = 1;
                    } else {
                        $cs['is_del'] = 0;
                    }
                    $car[$cs['cid']][] = $cs;
                }
                foreach ($list as &$item) {
                    // 判断是否赞过
                    if (!empty($zan_arr["3_{$item['id']}"])) {
                        $item['is_zan'] = 1;
                    } else {
                        $item['is_zan'] = 0;
                    }
                    $diff = time() - $item['addtime'];
                    if ($diff < 60) {
                        $item['addtime'] = '刚刚';
                    } elseif ($diff < 3600) {
                        $item['addtime'] = intval($diff / 60) . '分钟前';
                    } elseif ($diff < 86400) {
                        $item['addtime'] = intval($diff / 3600) . '小时前';
                    } else {
                        $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                    }
                    // 判断是否可以删除评论
                    if ($dynamic['is_del'] || $item['uid'] === $uid) {
                        $item['is_del'] = 1;
                    } else {
                        $item['is_del'] = 0;
                    }
                    if (!empty($car[$item['id']])) {
                        $item['reply'] = $car[$item['id']];
                    }
                }
            }
            $dynamic['comment'] = $list;
            $dc->modifyField('is_tip', 1, ['userid' => $uid, 'did' => $did]);
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $dynamic]);exit;
    }

    /**
     * 删除动态
     * @author 贺强
     * @time   2019-01-23 16:03:45
     */
    public function del_dynamic()
    {
        $param = $this->param;
        if (empty($param['id'])) {
            $msg = ['status' => 1, 'info' => '要删除的ID不能为空'];
        } elseif (empty($param['type'])) {
            $msg = ['status' => 3, 'info' => '要删除的类型不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $id   = $param['id'];
        $type = intval($param['type']);
        if ($type === 1) {
            $d   = new TDynamicModel();
            $res = $d->delDynamic($id);
        } else {
            $dc  = new TDynamicCommentModel();
            $res = $dc->del_comment($id);
        }
        if ($res !== true) {
            echo json_encode(['status' => $res, 'info' => '删除失败']);exit;
        }
        echo json_encode(['status' => 0, 'info' => '删除成功']);exit;
    }

    /**
     * 获取话题
     * @author 贺强
     * @time   2019-01-23 10:55:27
     * @param  TTopicModel $t TTopicModel 实例
     */
    public function get_topic(TTopicModel $t)
    {
        $param = $this->param;
        $list  = $t->getList(['status' => 1], ['id', 'title'], null, 'sort');
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 添加房间
     * @author 贺强
     * @time   2019-01-23 12:00:44
     * @param  TRoomModel $r TRoomModel 实例
     */
    public function add_room(TRoomModel $r)
    {
        $param = $this->param;
        if (empty($param['uid1']) || empty($param['uid2'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid1 = intval($param['uid1']);
        $uid2 = intval($param['uid2']);
        $name = "room_{$uid1}_{$uid2}";
        $nam2 = "room_{$uid2}_{$uid1}";
        $room = $r->getModel(['name' => ['in', [$name, $nam2]]], ['id', 'is_friend', 'uid1', 'nickname1', 'uid2', 'nickname2']);
        if ($room) {
            $nickname = $room['nickname1'];
            if ($uid1 === $room['uid1']) {
                $nickname = $room['nickname2'];
            }
            echo json_encode(['status' => 0, 'info' => '创建成功', 'data' => ['room_id' => $room['id'], 'nickname' => $nickname, 'is_friend' => $room['is_friend']]]);exit;
        }
        // 房间名称
        $param['name'] = $name;
        // 获取说话者昵称、头像、性别
        $u     = new TUserModel();
        $users = $u->getList(['id' => ['in', [$uid1, $uid2]]], ['id', 'nickname', 'avatar', 'sex']);
        foreach ($users as $user) {
            if ($user['id'] === $uid1) {
                $param['nickname1'] = $user['nickname'];
                $param['avatar1']   = $user['avatar'];
                $param['sex1']      = $user['sex'];
            } elseif ($user['id'] === $uid2) {
                $param['nickname2'] = $user['nickname'];
                $param['avatar2']   = $user['avatar'];
                $param['sex2']      = $user['sex'];
                // 聊天对象
                $nickname = $user['nickname'];
            }
        }
        $f     = new TFriendModel();
        $count = $f->getCount("((uid1=$uid1 and uid2=$uid2) or (uid1=$uid2 and uid2=$uid1)) and is_friend=1");
        if ($count) {
            $param['is_friend'] = $count;
        }
        $res = $r->add($param);
        if (!$res) {
            echo json_encode(['status' => 4, 'info' => '创建失败']);exit;
        }
        echo json_encode(['status' => 0, 'info' => '创建成功', 'data' => ['room_id' => $res, 'nickname' => $nickname, 'is_friend' => $count]]);exit;
    }

    /**
     * 聊天
     * @author 贺强
     * @time   2019-01-23 11:46:26
     * @param  TChatModel $c TChatModel 实例
     */
    public function chat(TChatModel $c)
    {
        $param = $this->param;
        if (empty($param['room_id'])) {
            $msg = ['status' => 1, 'info' => '房间ID不能为空'];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 3, 'info' => '说话者ID不能为空'];
        } elseif (empty($param['content'])) {
            $msg = ['status' => 5, 'info' => '聊天内容不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $room_id   = $param['room_id'];
        $uid       = $param['uid'];
        $is_friend = 0;
        if (!empty($param['is_friend'])) {
            $is_friend = intval($param['is_friend']);
        }
        unset($param['is_friend']);
        if (!$is_friend) {
            // 如果不是好友，一天只能发两条信息
            $count = $c->getCount(['addtime' => ['gt', time() - 86400], 'room_id' => $room_id, 'uid' => $uid]);
            if ($count >= 2) {
                $count = $c->getCount(['room_id' => $room_id, 'uid' => ['<>', $uid]]);
                if ($count === 0) {
                    echo json_encode(['status' => 11, 'info' => '你们还不是好友，若对方没回复，24小时内只能发两条信息，赶快互相关注成为好友吧']);exit;
                }
            }
        }
        $u    = new TUserModel();
        $user = $u->getModel(['id' => $uid], ['nickname', 'avatar', 'sex']);
        if (!empty($user)) {
            $param = array_merge($param, $user);
        }
        $param['addtime'] = time();
        // 添加
        $res = $c->add($param);
        if (!$res) {
            echo json_encode(['status' => 4, 'info' => '添加失败']);exit;
        }
        $r = new TRoomModel();
        $r->modify(['content' => $param['content'], 'chat_time' => time()], ['id' => $room_id]);
        echo json_encode(['status' => 0, 'info' => '添加成功']);exit;
    }

    /**
     * 修改聊天信息读取状态
     * @author 贺强
     * @time   2019-01-24 19:25:04
     * @param  TChatModel $c TChatModel 实例
     */
    public function mdchattip(TChatModel $c)
    {
        $param = $this->param;
        if (empty($param['room_id'])) {
            $msg = ['status' => 1, 'info' => '房间ID不能为空'];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 3, 'info' => '当前登录用户不能为空'];
        } elseif (empty($param['overtime'])) {
            $msg = ['status' => 5, 'info' => '离开页面时间不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $room_id  = $param['room_id'];
        $uid      = $param['uid'];
        $overtime = strtotime($param['overtime']);
        $c->modifyField('is_read', 1, ['room_id' => $room_id, 'uid' => ['<>', $uid], 'addtime' => ['lt', $overtime]]);
        echo json_encode(['status' => 0, 'info' => '修改成功']);exit;
    }

    /**
     * 获取对话列表
     * @author 贺强
     * @time   2019-01-24 09:59:58
     * @param  TRoomModel $r TRoomModel 实例
     */
    public function get_rooms(TRoomModel $r)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid   = intval($param['uid']);
        $where = ['uid1|uid2' => $uid, 'chat_time' => ['>', 0]];
        $page  = 1;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        $pagesize = 10;
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $rooms = $r->getList($where, true, "$page,$pagesize", 'chat_time desc');
        $ids   = array_column($rooms, 'id');
        // 获取未读消息数量
        $c     = new TChatModel();
        $chats = $c->getList(['room_id' => ['in', $ids], 'uid' => ['<>', $uid], 'is_read' => 0], ['count(*)' => 'count', 'room_id'], null, '', 'room_id');
        $chats = array_column($chats, 'count', 'room_id');
        $data  = [];
        foreach ($rooms as &$room) {
            // 显示发布时间
            $diff = time() - $room['chat_time'];
            if ($diff < 60) {
                $chat_time = '刚刚';
            } elseif ($diff < 3600) {
                $chat_time = intval($diff / 60) . '分钟前';
            } elseif ($diff < 86400) {
                $chat_time = intval($diff / 3600) . '小时前';
            } else {
                $chat_time = date('Y-m-d H:i:s', $room['chat_time']);
            }
            $count = 0;
            if (!empty($chats[$room['id']])) {
                $count = $chats[$room['id']];
            }
            $item = ['id' => $room['id'], 'is_friend' => $room['is_friend'], 'count' => $count, 'content' => $room['content'], 'chat_time' => $chat_time];
            if ($room['uid1'] === $uid) {
                $item = array_merge($item, ['uid' => $room['uid2'], 'nickname' => $room['nickname2'], 'avatar' => $room['avatar2'], 'sex' => $room['sex2']]);
            } else {
                $item = array_merge($item, ['uid' => $room['uid1'], 'nickname' => $room['nickname1'], 'avatar' => $room['avatar1'], 'sex' => $room['sex1']]);
            }
            $data[] = $item;
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $data]);exit;
    }

    /**
     * 获取聊天内容
     * @author 贺强
     * @time   2019-01-24 10:24:04
     * @param  TChatModel $c TChatModel 实例
     */
    public function get_chatlog(TChatModel $c)
    {
        $param = $this->param;
        if (empty($param['room_id'])) {
            $msg = ['status' => 1, 'info' => '房间ID不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $room_id   = $param['room_id'];
        $starttime = time() - 86400;
        $where     = "room_id=$room_id and (addtime>$starttime or is_read=0)";
        $page      = 1;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        $pagesize = 10;
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $list = $c->getList($where, true, "$page,$pagesize", 'addtime desc');
        $list = array_column($list, null, 'addtime');
        ksort($list);
        $list = array_merge($list);
        foreach ($list as &$item) {
            // 显示发布时间
            $diff = time() - $item['addtime'];
            if ($diff < 60) {
                $item['addtime'] = '刚刚';
            } elseif ($diff < 3600) {
                $item['addtime'] = intval($diff / 60) . '分钟前';
            } elseif ($diff < 86400) {
                $item['addtime'] = intval($diff / 3600) . '小时前';
            } else {
                $item['addtime'] = date('Y/m/d H:i:s', $item['addtime']);
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 获取游戏
     * @author 贺强
     * @time   2019-01-23 17:41:46
     * @param  TGameModel $g TGameModel 实例
     */
    public function get_games(TGameModel $g)
    {
        $list = $g->getList(['status' => 1], ['id', 'name', 'logo', 'type'], null, 'sort');
        foreach ($list as &$item) {
            if (!empty($item['logo']) && strpos($item['logo'], 'http://') === false && strpos($item['logo'], 'https://') === false) {
                $item['logo'] = config('WEBSITE') . $item['logo'];
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 添加游戏技能
     * @author 贺强
     * @time   2019-01-23 17:43:07
     * @param  TUserGameModel $ug TUserGameModel 实例
     */
    public function add_game(TUserGameModel $ug)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空'];
        } elseif (empty($param['gid'])) {
            $msg = ['status' => 3, 'info' => '游戏ID不能为空'];
        } elseif (empty($param['online'])) {
            $msg = ['status' => 9, 'info' => '在线时间段不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid  = $param['uid'];
        $gid  = $param['gid'];
        $g    = new TGameModel();
        $game = $g->getModel(['id' => $gid]);
        if ($game) {
            $param['name'] = $game['name'];
            $param['logo'] = $game['logo'];
        }
        $count = $ug->getCount(['uid' => $uid, 'gid' => $gid]);
        if ($count) {
            $res = $ug->modify($param, ['uid' => $uid, 'gid' => $gid]);
            if ($res === false) {
                echo json_encode(['status' => 4, 'info' => '失败']);exit;
            }
            echo json_encode(['status' => 0, 'info' => '成功']);exit;
        }
        $res = $ug->add($param);
        if (!$res) {
            echo json_encode(['status' => 40, 'info' => '添加失败']);exit;
        }
        echo json_encode(['status' => 0, 'info' => '添加成功']);exit;
    }

    /**
     * 个人中心
     * @author 贺强
     * @time   2019-01-23 18:45:54
     * @param  TUserModel $u TUserModel 实例
     */
    public function userinfo(TUserModel $u)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空'];
        } elseif (empty($param['tid'])) {
            $msg = ['status' => 3, 'info' => '浏览者ID不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid  = $param['uid'];
        $user = $u->getModel(['id' => $uid], ['id', 'nickname', 'avatar', 'age', 'sex', 'inyear', 'school', 'department', 'grade']);
        if ($user) {
            $ug    = new TUserGameModel();
            $games = $ug->getList(['uid' => $uid], ['gid', 'name', 'online', 'logo']);
            foreach ($games as &$gm) {
                if (!empty($gm['logo']) && strpos($gm['logo'], 'http://') === false && strpos($gm['logo'], 'https://') === false) {
                    $gm['logo'] = config('WEBSITE') . $gm['logo'];
                }
            }
            // 拥有的游戏技能
            $user['games'] = $games;
            // 获取关注数
            $follow         = $this->friends(['uid' => $uid, 'type' => 1, 'is_count' => 1]);
            $user['follow'] = $follow;
            // 获取粉丝数
            $fans         = $this->friends(['uid' => $uid, 'type' => 2, 'is_count' => 1]);
            $user['fans'] = $fans;
            // 是否关注
            $tid   = $param['tid'];
            $f     = new TFriendModel();
            $count = $f->getCount("(uid1=$tid and follow1=1 and uid2=$uid) or (uid2=$tid and follow2=1 and uid1=$uid)");
            // 关注赋值
            $user['is_follow'] = $count;
            // 是否点赞
            $p     = new TPraiseModel();
            $count = $p->getCount(['obj_id' => $uid, 'uid' => $tid, 'type' => 1]);
            // 点赞赋值
            $user['is_zan'] = $count;
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $user]);exit;
    }

    /**
     * 获取个人中心用户动态
     * @author 贺强
     * @time   2019-01-23 21:20:04
     * @param  TDynamicModel $d TDynamicModel 实例
     */
    public function userdynamic(TDynamicModel $d)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空'];
        } elseif (empty($param['tid'])) {
            $msg = ['status' => 3, 'info' => '浏览者ID不能为空'];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid = $param['uid'];
        $tid = $param['tid'];
        $f   = new TFriendModel();
        $fnd = $f->getCount("(uid1=$tid and follow1=1 and uid2=$uid) or (uid2=$tid and follow2=1 and uid1=$uid)");
        // 判断浏览者有没有权限查看该用户的动态
        $enable = 0;
        if ($fnd) {
            // 如果浏览者关注了该用户则可以查看
            $enable = 1;
        } else {
            $u     = new TUserModel();
            $users = $u->getList(['id' => ['in', [$uid, $tid]]], ['id', 'circle']);
            $users = array_column($users, 'circle', 'id');
            if (!empty($users[$uid])) {
                $ucir = explode(',', $users[$uid]);
            }
            if (!empty($users[$tid])) {
                $tcir = explode(',', $users[$tid]);
            }
            // 如果浏览者和该用户拥有共同的圈子则可以查看
            if (!empty($ucir) && !empty($tcir)) {
                foreach ($ucir as $uc) {
                    if (in_array($uc, $tcir)) {
                        $enable = 1;
                        break;
                    }
                }
            }
        }
        $where = ['uid' => $uid];
        if (!$enable) {
            $where['is_open'] = 1;
        }
        $page = 1;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        $pagesize = 10;
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $list = $d->getList($where, true, "$page,$pagesize", 'addtime desc');
        if ($list) {
            // 获取我的点赞
            $p    = new TPraiseModel();
            $zans = $p->getList(['uid' => $tid, 'type' => 1], ['obj_id', 'uid']);
            $zans = array_column($zans, 'uid', 'obj_id');
            // 获取动态话题
            $ft    = new TTopicModel();
            $topic = $ft->getList([], ['id', 'title']);
            $topic = array_column($topic, 'title', 'id');
            foreach ($list as &$item) {
                if ($item['uid'] === $tid) {
                    $item['is_del'] = 1;
                } else {
                    $item['is_del'] = 0;
                }
                // 动态话题
                $tps = [];
                if (!empty($item['topic'])) {
                    $topics = explode(',', $item['topic']);
                    foreach ($topics as $t) {
                        if (!empty($topic[$t])) {
                            $tps[] = ['id' => $t, 'title' => $topic[$t]];
                        }
                    }
                }
                $item['topic'] = $tps;
                // 当前用户是否赞过此动态
                if (!empty($zans[$item['id']])) {
                    $item['is_zan'] = 1;
                } else {
                    $item['is_zan'] = 0;
                }
                // 显示发布时间
                $diff = time() - $item['addtime'];
                if ($diff < 60) {
                    $item['addtime'] = '刚刚';
                } elseif ($diff < 3600) {
                    $item['addtime'] = intval($diff / 60) . '分钟前';
                } elseif ($diff < 86400) {
                    $item['addtime'] = intval($diff / 3600) . '小时前';
                } else {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
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
     * 获取学校或院系
     * @author 贺强
     * @time   2019-01-25 11:12:02
     * @param  TSchoolModel $s TSchoolModel 实例
     */
    public function get_outfit(TSchoolModel $s)
    {
        $param = $this->param;
        if ($param['pid']) {
            $depart = $s->getList(['pid' => $param['pid']], ['name']);
            $data   = ['depart' => $depart];
        } else {
            $school = $s->getList(['pid' => 0], ['id', 'name']);
            $depart = $s->getList(['pid' => 1], ['name']);
            $age    = [['name' => '80后'], ['name' => '85后'], ['name' => '90后'], ['name' => '95后'], ['name' => '00后'], ['name' => '05后'], ['name' => '10后']];
            $start  = 2000;
            $end    = date('Y');
            $grade  = [];
            for ($i = $start; $i <= $end; $i++) {
                $gd      = substr($i, 2) . '级';
                $grade[] = ['name' => $gd];
            }
            $data = ['age' => $age, 'school' => $school, 'depart' => $depart, 'grade' => $grade];
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $data]);exit;
    }
}
