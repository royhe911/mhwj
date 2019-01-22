<?php
namespace app\api\controller;

use app\common\model\FriendChatModel;
use app\common\model\FriendCommentModel;
use app\common\model\FriendModel;
use app\common\model\FriendMoodModel;
use app\common\model\FriendPchatModel;
use app\common\model\FriendProomModel;
use app\common\model\FriendTopicModel;
use app\common\model\FriendUroomModel;
use app\common\model\FriendZanModel;
use app\common\model\UserModel;

/**
 * FriendApi-控制器
 * @author 贺强
 * @time   2019-01-11 12:30:34
 */
class Friend extends \think\Controller
{
    private $param = [];

    /**
     * 构造函数
     * @author 贺强
     * @time   2019-01-11 14:19:08
     */
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
     * 获取主题
     * @author 贺强
     * @time   2019-01-11 14:05:01
     * @param  FriendTopicModel $ft FriendTopicModel 实例
     */
    public function get_topic(FriendTopicModel $ft, $is_slft = false)
    {
        $where = ['status' => 1];
        $list  = $ft->getList($where, ['id', 'title'], '', 'sort');
        if ($list) {
            foreach ($list as &$item) {
                if (strpos($item['title'], '#') === false) {
                    $item['title'] = '#' . $item['title'];
                }
            }
        }
        if ($is_slft) {
            return array_column($list, 'title', 'id');
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 发布心情
     * @author 贺强
     * @time   2019-01-11 14:18:12
     * @param  FriendMoodModel $fm FriendMoodModel 实例
     */
    public function release(FriendMoodModel $fm)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 3, 'info' => '用户ID不能为空', 'data' => null];
        } elseif (empty($param['content'])) {
            $msg = ['status' => 1, 'info' => '心情描述不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $u    = new UserModel();
        $user = $u->getModel(['id' => $param['uid']], ['nickname', 'avatar', 'sex']);
        if (!empty($user)) {
            $param['nickname'] = $user['nickname'];
            $param['avatar']   = $user['avatar'];
            $param['sex']      = $user['sex'];
        }
        $param['addtime'] = time();
        if (!empty($param['topic'])) {
            $ft = new FriendTopicModel();
            $ft->increment('count', ['id' => ['in', $param['topic']]]);
        }
        // 添加
        $res = $fm->add($param);
        if (!$res) {
            echo json_encode(['status' => 40, 'info' => '发布失败', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '发布成功', 'data' => null]);exit;
    }

    /**
     * 评论/回复
     * @author 贺强
     * @time   2019-01-11 15:46:00
     * @param  FriendCommentModel $fc FriendCommentModel 实例
     */
    public function comment(FriendCommentModel $fc)
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

    /**
     * 点赞/取消点赞
     * @author 贺强
     * @time   2019-01-11 14:58:11
     * @param  FriendZanModel $fz FriendZanModel 实例
     */
    public function zan(FriendZanModel $fz)
    {
        $param = $this->param;
        if (empty($param['obj_id'])) {
            $msg = ['status' => 1, 'info' => '心情或评论ID不能为空', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 3, 'info' => '用户ID不能为空', 'data' => null];
        } elseif (empty($param['type'])) {
            $msg = ['status' => 5, 'info' => '类型不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $param['addtime'] = time();
        // 添加
        $res = $fz->do_zan($param);
        if ($res !== true) {
            echo json_encode(['status' => $res, 'info' => '失败', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '成功', 'data' => null]);exit;
    }

    /**
     * 获取心情
     * @author 贺强
     * @time   2019-01-11 16:33:34
     * @param  FriendMoodModel $fm FriendMoodModel 实例
     */
    public function get_moods(FriendMoodModel $fm)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $where = '1';
        $order = 'addtime desc';
        if (!empty($param['is_recommend'])) {
            $where .= ' and is_recommend=1';
            $order = 'sort';
        }
        if (!empty($param['topic'])) {
            $where .= " and find_in_set({$param['topic']},topic)";
        }
        $uid = $param['uid'];
        if (!empty($param['is_follow'])) {
            $f    = new FriendModel();
            $fris = $f->getList("(uid1={$uid} and follow1=1) or (uid2={$uid} and follow2=1)", ['uid1', 'uid2']);
            $id1s = array_column($fris, 'uid1');
            $id1s = array_unique($id1s);
            // print_r($id1s);
            $id2s = array_column($fris, 'uid2');
            $id2s = array_unique($id2s);
            // print_r($id2s);
            $ids = array_merge($id1s, $id2s);
            $ids = array_unique($ids);
            if (!empty($ids)) {
                $where .= " and uid in ($ids)";
            } else {
                echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => null]);exit;
            }
        }
        // print_r($where);exit;
        $page = 1;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        $pagesize = 10;
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $list = $fm->getList($where, ['id', 'uid', 'nickname', 'avatar', 'content', 'origin', 'type', 'thumb', 'pic', 'topic', 'zan_count', 'pl_count', 'addtime'], "$page,$pagesize", $order);
        if ($list) {
            $f     = new FriendModel();
            $fnds  = $this->friends($uid);
            $fnds  = array_column($fnds, 'nickname', 'uid');
            $fz    = new FriendZanModel();
            $zans  = $fz->getList(['uid' => $uid, 'type' => 1], ['obj_id', 'uid']);
            $zans  = array_column($zans, 'uid', 'obj_id');
            $ft    = new FriendTopicModel;
            $topic = $ft->getList([], ['id', 'title']);
            $topic = array_column($topic, 'title', 'id');
            foreach ($list as &$item) {
                $tps = [];
                if (!empty($item['topic'])) {
                    $topics = explode(',', $item['topic']);
                    foreach ($topics as $t) {
                        $tps[] = ['id' => $t, 'title' => $topic[$t]];
                    }
                }
                $item['topic'] = $tps;
                if (!empty($fnds[$item['uid']])) {
                    $item['is_follow'] = 1;
                } else {
                    $item['is_follow'] = 0;
                }
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
                $thumbs = [];
                if (!empty($item['thumb'])) {
                    $thumbs = explode(',', $item['thumb']);
                    foreach ($thumbs as &$thumb) {
                        if (strpos($thumb, 'http://') === false && strpos($thumb, 'https://') === false) {
                            $thumb = config('WEBSITE') . $thumb;
                        }
                    }
                }
                $item['thumb'] = $thumbs;
                $pics          = [];
                if (!empty($item['pic'])) {
                    $pics = explode(',', $item['pic']);
                    foreach ($pics as &$pic) {
                        if (strpos($pic, 'http://') === false && strpos($pic, 'https://') === false) {
                            $pic = config('WEBSITE') . $pic;
                        }
                    }
                }
                $item['pic'] = $pics;
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 获取心情详情
     * @author 贺强
     * @time   2019-01-14 11:22:44
     * @param  FriendMoodModel $fm FriendMoodModel 实例
     */
    public function get_mood_info(FriendMoodModel $fm)
    {
        $param = $this->param;
        if (empty($param['mood_id'])) {
            $msg = ['status' => 1, 'info' => 'ID不能为空', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 3, 'info' => '用户ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid     = $param['uid'];
        $mood_id = $param['mood_id'];
        $mood    = $fm->getModel(['id' => $mood_id]);
        if ($mood) {
            $ft    = new FriendTopicModel();
            $topic = $ft->getList([], ['id', 'title']);
            $topic = array_column($topic, 'title', 'id');
            $tps   = [];
            if (!empty($mood['topic'])) {
                $topics = explode(',', $mood['topic']);
                foreach ($topics as $t) {
                    $tps[] = ['id' => $t, 'title' => $topic[$t]];
                }
            }
            $mood['topic'] = $tps;
            $zan_arr       = [];
            // 获取当前用户是否关注此用户
            $f     = new FriendModel();
            $where = "(uid1=$uid and follow1=1 and uid2={$mood['uid']}) or (uid2=$uid and follow2=1 and uid1={$mood['uid']})";
            $count = $f->getCount($where);
            if ($count) {
                $mood['is_follow'] = 1;
            } else {
                $mood['is_follow'] = 0;
            }
            $thumbs = [];
            if (!empty($mood['thumb'])) {
                $thumbs = explode(',', $mood['thumb']);
                foreach ($thumbs as &$thumb) {
                    if (strpos($thumb, 'http://') === false && strpos($thumb, 'https://') === false) {
                        $thumb = config('WEBSITE') . $thumb;
                    }
                }
            }
            $mood['thumb'] = $thumbs;
            $pics          = [];
            if (!empty($mood['pic'])) {
                $pics = explode(',', $mood['pic']);
                foreach ($pics as &$pic) {
                    if (strpos($pic, 'http://') === false && strpos($pic, 'https://') === false) {
                        $pic = config('WEBSITE') . $pic;
                    }
                }
            }
            $mood['pic'] = $pics;
            // 取得当前用户赞过的
            $fz   = new FriendZanModel();
            $zans = $fz->getList(['uid' => $uid], ['obj_id', 'type', 'uid']);
            foreach ($zans as $z) {
                $zan_arr[$z['type'] . '_' . $z['obj_id']] = $z;
            }
            if (!empty($zan_arr["1_{$mood['id']}"])) {
                $mood['is_zan'] = 1;
            } else {
                $mood['is_zan'] = 0;
            }
            // 显示发布时间
            $diff = time() - $mood['addtime'];
            if ($diff < 60) {
                $mood['addtime'] = $diff . '秒前';
            } elseif ($diff < 3600) {
                $mood['addtime'] = intval($diff / 60) . '分钟前';
            } elseif ($diff < 86400) {
                $mood['addtime'] = intval($diff / 3600) . '小时前';
            } else {
                $mood['addtime'] = date('Y-m-d H:i:s', $mood['addtime']);
            }
            $fc   = new FriendCommentModel();
            $list = $fc->getList(['mood_id' => $param['mood_id'], 'type' => 1], ['id', 'uid', 'nickname', 'avatar', 'sex', 'content', 'zan_count', 'addtime'], null, 'addtime desc');
            if ($list) {
                $cos = $fc->getList(['mood_id' => $mood_id, 'type' => 2], ['id', 'mood_id', 'obj_id', 'uid', 'nickname', 'sex', 'content', 'addtime', 'type']);
                $rpl = [];
                $rpy = array_column($cos, null, 'id');
                foreach ($cos as &$cs) {
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
                    if (!empty($rpy[$cs['obj_id']])) {
                        $ry = $rpy[$cs['obj_id']];
                        // 赋值
                        $cs['ruid']      = $ry['uid'];
                        $cs['rnickname'] = $ry['nickname'];
                    } else {
                        $cs['ruid']      = 0;
                        $cs['rnickname'] = '';
                    }
                }
                foreach ($list as &$item) {
                    // 判断是否赞过
                    if (!empty($zan_arr["2_{$item['id']}"])) {
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
                    $rdata = [];
                    $this->get_reply($item['id'], $cos, $rdata);
                    $item['reply'] = $rdata;
                }
            }
            $mood['comment'] = $list;
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $mood]);exit;
    }

    /**
     * 获取回复信息
     * @author 贺强
     * @time   2019-01-17 19:01:40
     * @param  integer $fid 评论ID
     * @param  array   $arr 回复数组
     */
    private function get_reply($fid, $arr, &$rdata = [])
    {
        foreach ($arr as $k => $ar) {
            if ($ar['obj_id'] === $fid) {
                $rdata[] = $ar;
                unset($arr[$k]);
                $this->get_reply($ar['id'], $arr, $rdata);
            }
        }
    }

    /**
     * 删除心情/评论/回复
     * @author 贺强
     * @time   2019-01-14 16:08:06]
     */
    public function del_mood()
    {
        $param = $this->param;
        if (empty($param['id'])) {
            $msg = ['status' => 1, 'info' => '要删除的ID不能为空', 'data' => null];
        } elseif (empty($param['type'])) {
            $msg = ['status' => 3, 'info' => '要删除的类型不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $id   = $param['id'];
        $type = intval($param['type']);
        if ($type === 1) {
            $fm  = new FriendMoodModel();
            $res = $fm->del_mood($id);
        } else {
            $fc  = new FriendCommentModel();
            $res = $fc->del_comment($id);
        }
        if ($res !== true) {
            echo json_encode(['status' => $res, 'info' => '删除失败', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '删除成功', 'data' => null]);exit;
    }

    /**
     * 获取热门话题
     * @author 贺强
     * @time   2019-01-15 11:14:47
     * @param  FriendTopicModel $fm FriendTopicModel 实例
     */
    public function hot_topic(FriendTopicModel $ft)
    {
        $where = ['status' => 1];
        $list  = $ft->getList($where, ['id', 'title', 'count'], "1,3", "count desc");
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 关注/取消关注
     * @author 贺强
     * @time   2019-01-15 12:05:28
     * @param  FriendModel $f FriendModel 实例
     */
    public function follow(FriendModel $f)
    {
        $param = $this->param;
        if (empty($param['uid1'])) {
            $msg = ['status' => 1, 'info' => '关注者ID不能为空', 'data' => null];
        } elseif (empty($param['uid2'])) {
            $msg = ['status' => 3, 'info' => '被关注者ID不能为空', 'data' => null];
        } elseif ($param['uid1'] === $param['uid2']) {
            $msg = ['status' => 5, 'info' => '不能关注自己', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $u     = new UserModel();
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
        }
        if (!$res) {
            echo json_encode(['status' => 40, 'info' => '关注失败', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '成功', 'data' => null]);exit;
    }

    /**
     * 获取我的关注/粉丝/朋友
     * @author 贺强
     * @time   2019-01-15 16:04:39
     * @param  FriendModel $f FriendModel 实例
     */
    public function friends($uid = 0)
    {
        $param = $this->param;
        $self  = false;
        if ($uid) {
            $param['uid']  = $uid;
            $param['type'] = 1;
            $self          = true;
        }
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空', 'data' => null];
        } elseif (empty($param['type'])) {
            $msg = ['status' => 3, 'info' => '类型不能为空', 'data' => null];
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
        $f = new FriendModel();
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
            foreach ($list as &$item) {
                // 过滤掉本人只取好友信息
                if ($item['uid1'] === $uid) {
                    unset($item['uid1'], $item['nickname1'], $item['avatar1']);
                    $item['uid']      = $item['uid2'];
                    $item['nickname'] = $item['nickname2'];
                    $item['avatar']   = $item['avatar2'];
                    $item['sex']      = $item['sex2'];
                    unset($item['uid2'], $item['nickname2'], $item['avatar2'], $item['sex2']);
                } elseif ($item['uid2'] === $uid) {
                    unset($item['uid2'], $item['nickname2'], $item['avatar2']);
                    $item['uid']      = $item['uid1'];
                    $item['nickname'] = $item['nickname1'];
                    $item['avatar']   = $item['avatar1'];
                    $item['sex']      = $item['sex1'];
                    unset($item['uid1'], $item['nickname1'], $item['avatar1'], $item['sex1']);
                }
            }
            if ($self) {
                return $list;
            }
            echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
        }
    }

    /**
     * 添加私聊房间
     * @author 贺强
     * @time   2019-01-17 10:19:22
     * @param  FriendUroomModel $fu FriendUroomModel 实例
     */
    public function add_room(FriendUroomModel $fu)
    {
        $param = $this->param;
        if (empty($param['uid1']) || empty($param['uid2'])) {
            $msg = ['status' => 1, 'info' => '聊天者ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid1  = intval($param['uid1']);
        $uid2  = intval($param['uid2']);
        $name  = "room_{$uid1}_{$uid2}";
        $name2 = "room_{$uid2}_{$uid1}";
        $room  = $fu->getModel(['name' => ['in', [$name, $name2]]], ['id']);
        if ($room) {
            echo json_encode(['status' => 0, 'info' => '添加成功1', 'data' => $room['id']]);exit;
        }
        $u     = new UserModel();
        $users = $u->getList(['id' => ['in', [$uid1, $uid2]]], ['id', 'nickname', 'avatar']);
        $users = array_column($users, null, 'id');
        foreach ($users as $user) {
            if ($user['id'] === $uid1) {
                $param['nickname1'] = $user['nickname'];
                $param['avatar1']   = $user['avatar'];
            } elseif ($user['id'] === $uid2) {
                $param['nickname2'] = $user['nickname'];
                $param['avatar2']   = $user['avatar'];
            }
        }
        $param['name']    = $name;
        $param['addtime'] = time();
        // 添加
        $res = $fu->add($param);
        if (!$res) {
            echo json_encode(['status' => 40, 'info' => '添加失败', 'data' => $res]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '添加成功', 'data' => null]);exit;
    }

    /**
     * 删除未聊过天的房间
     * @author 贺强
     * @time   2019-01-17 11:59:48
     * @param  FriendUroomModel $fu FriendUroomModel 实例
     */
    public function del_room(FriendUroomModel $fu)
    {
        $param = $this->param;
        if (empty($param['uid1']) || empty($param['uid2'])) {
            $msg = ['status' => 1, 'info' => '聊天者ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid1  = intval($param['uid1']);
        $uid2  = intval($param['uid2']);
        $name  = "room_{$uid1}_{$uid2}";
        $name2 = "room_{$uid2}_{$uid1}";
        $room  = $fu->getModel(['name' => ['in', [$name, $name2]], 'chat_time' => 0, 'content' => ''], ['id']);
        if ($room) {
            $fu->delById($room['id']);
        }
        echo json_encode(['status' => 0, 'info' => '成功', 'data' => null]);exit;
    }

    /**
     * 对话(私聊)
     * @author 贺强
     * @time   2019-01-17 09:48:37
     * @param  FriendChatModel $fc FriendChatModel 实例
     */
    public function friend_chat(FriendChatModel $fc)
    {
        $param = $this->param;
        if (empty($param['room_id'])) {
            $msg = ['status' => 1, 'info' => '房间ID不能为空', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 3, 'info' => '说话者ID不能为空', 'data' => null];
        } elseif (empty($param['content'])) {
            $msg = ['status' => 5, 'info' => '说话内容不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid  = $param['uid'];
        $u    = new UserModel();
        $user = $u->getModel(['id' => $uid], ['nickname', 'avatar', 'sex']);
        if (!empty($user)) {
            $param['nickname'] = $user['nickname'];
            $param['avatar']   = $user['avatar'];
            $param['sex']      = $user['sex'];
        }
        $param['addtime'] = time();
        // 添加
        $res = $fc->add($param);
        if (!$res) {
            echo json_encode(['status' => 40, 'info' => '添加失败', 'data' => null]);exit;
        }
        $fu = new FriendUroomModel();
        $fu->modify(['content' => $param['content'], 'chat_time' => time()], ['id' => $param['room_id']]);
        echo json_encode(['status' => 0, 'info' => '添加成功', 'data' => null]);exit;
    }

    /**
     * 获取用户对话列表
     * @author 贺强
     * @time   2019-01-17 11:38:47
     * @param  FriendUroomModel $fu FriendUroomModel 实例
     */
    public function get_chats(FriendUroomModel $fu)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $page = 1;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        $pagesize = 10;
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $uid  = intval($param['uid']);
        $list = $fu->getList(['uid1|uid2' => $uid, 'chat_time' => ['>', 0], 'content' => ['<>', '']], ['id', 'uid1', 'uid2', 'nickname1', 'nickname2', 'avatar1', 'avatar2', 'content', 'chat_time'], "$page,$pagesize", 'chat_time desc');
        $data = [];
        foreach ($list as $item) {
            $chat_time = $item['chat_time'];
            if ($chat_time > strtotime(date('Y-m-d'))) {
                $chat_time = date('H:i', $chat_time);
            } else {
                $chat_time = date('Y-m-d', $chat_time);
            }
            if ($item['uid1'] === $uid) {
                $data[] = ['nickname' => $item['nickname2'], 'avatar' => $item['avatar2'], 'content' => $item['content'], 'chat_time' => $chat_time];
            } elseif ($item['uid2'] === $uid) {
                $data[] = ['nickname' => $item['nickname1'], 'avatar' => $item['avatar1'], 'content' => $item['content'], 'chat_time' => $chat_time];
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $data]);exit;
    }

    /**
     * 获取私聊记录
     * @author 贺强
     * @time   2019-01-18 15:33:02
     * @param  FriendChatModel $fc FriendChatModel 实例
     */
    public function get_chat_log(FriendChatModel $fc)
    {
        $param = $this->param;
        $where = [];
        if (!empty($param['room_id'])) {
            $where = ['room_id' => $param['room_id']];
        } elseif (!empty($param['uid']) && !empty($param['friend_id'])) {
            $name  = "room_{$param['uid']}_{$param['friend_id']}";
            $name2 = "room_{$param['friend_id']}_{$param['uid']}";
            $fu    = new FriendUroomModel();
            $room  = $fu->getModel(['name' => ['in', [$name, $name2]]], ['id']);
            if (!empty($room)) {
                $where = ['room_id' => $room['id']];
            }
        }
        if (empty($where)) {
            echo json_encode(['status' => 1, 'info' => '非法操作', 'data' => null]);exit;
        }
        $list = $fc->getList($where, ['uid', 'avatar', 'content', 'addtime']);
        foreach ($list as &$item) {
            if ($item['addtime'] > strtotime(date('Y-m-d'))) {
                $item['showtime'] = date('H:i', $item['addtime']);
            } else {
                $item['showtime'] = date('Y/m/d H:i', $item['addtime']);
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 个人信息
     * @author 贺强
     * @time   2019-01-18 11:22:44
     */
    public function userinfo()
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空', 'data' => null];
        } elseif (empty($param['tid'])) {
            $msg = ['status' => 3, 'info' => '浏览者ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid   = $param['uid'];
        $tid   = $param['tid'];
        $u     = new UserModel();
        $user  = $u->getModel(['id' => $uid], ['id', 'nickname', 'avatar', 'sex']);
        $f     = new FriendModel();
        $w1    = "(uid1=$tid and follow1=1 and uid2=$uid) or (uid2=$tid and follow2=1 and uid1=$uid)";
        $count = $f->getCount($w1);
        // 是否关注
        $user['is_follow'] = 0;
        if ($count) {
            $user['is_follow'] = 1;
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $user]);exit;
    }

    /**
     * 获取用户动态
     * @author 贺强
     * @time   2019-01-18 11:38:04
     * @param  FriendMoodModel $fm FriendMoodModel 实例
     */
    public function dynamic(FriendMoodModel $fm)
    {
        $param = $this->param;
        if (empty($param['uid'])) {
            $msg = ['status' => 1, 'info' => '用户ID不能为空', 'data' => null];
        }
        if (empty($param['tid'])) {
            $msg = ['status' => 3, 'info' => '浏览者ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $page = 1;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        $pagesize = 10;
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $uid   = $param['uid'];
        $tid   = $param['tid'];
        $where = ['uid' => $uid];
        $list  = $fm->getList($where, ['id', 'addtime', 'content', 'type', 'zan_count', 'pl_count', 'thumb', 'pic', 'topic'], "$page,$pagesize", 'addtime desc');
        if ($list) {
            $ft    = new FriendTopicModel();
            $topic = $ft->getList([], ['id', 'title']);
            $topic = array_column($topic, 'title', 'id');
            $fz    = new FriendZanModel();
            $zans  = $fz->getList(['uid' => $tid, 'type' => 1], ['obj_id', 'uid']);
            $zans  = array_column($zans, 'uid', 'obj_id');
            foreach ($list as &$item) {
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
                $tps = [];
                if (!empty($item['topic'])) {
                    $topics = explode(',', $item['topic']);
                    foreach ($topics as $t) {
                        $tps[] = ['id' => $t, 'title' => $topic[$t]];
                    }
                }
                $item['topic'] = $tps;
                // 是否赞
                if (!empty($zans[$item['id']])) {
                    $item['is_zan'] = 1;
                } else {
                    $item['is_zan'] = 0;
                }
                // 缩略图
                $thumbs = [];
                if (!empty($item['thumb'])) {
                    $thumbs = explode(',', $item['thumb']);
                    foreach ($thumbs as &$thumb) {
                        if (strpos($thumb, 'http://') === false && strpos($thumb, 'https://') === false) {
                            $thumb = config('WEBSITE') . $thumb;
                        }
                    }
                }
                $item['thumb'] = $thumbs;
                // 原图
                $pics = [];
                if (!empty($item['pic'])) {
                    $pics = explode(',', $item['pic']);
                    foreach ($pics as &$pic) {
                        if (strpos($pic, 'http://') === false && strpos($pic, 'https://') === false) {
                            $pic = config('WEBSITE') . $pic;
                        }
                    }
                }
                $item['pic'] = $pics;
            }
        }
        $sum = $fm->getModel($where, ['count(*)' => 'count', 'sum(zan_count)' => 'total_zan']);
        if (empty($sum['total_zan'])) {
            $sum['total_zan'] = 0;
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => ['dynamic' => $list, 'sum' => $sum]]);exit;
    }

    /**
     * 获取公聊房间
     * @author 贺强
     * @time   2019-01-18 16:18:51
     * @param  FriendProomModel $fp FriendProomModel 实例
     * @param  FriendPchatModel $fc FriendPchatModel 实例
     */
    public function get_public_room(FriendProomModel $fp, FriendPchatModel $fc)
    {
        $list = $fp->getList(['status' => 1], ['id', 'name', 'bgcolor']);
        if ($list) {
            $time    = strtotime(date('Y-m-d'));
            $chats   = $fc->getList(['addtime' => ['gt', $time]], ['avatar', 'content', 'room_id']);
            $chatlog = [];
            foreach ($chats as &$chat) {
                $room_id = $chat['room_id'];
                unset($chat['room_id']);
                $chatlog[$room_id][] = $chat;
            }
            foreach ($list as &$item) {
                if (!empty($chatlog[$item['id']])) {
                    $item['chatlog'] = $chatlog[$item['id']];
                } else {
                    $item['chatlog'] = [];
                }
            }
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
    }

    /**
     * 公共聊天
     * @author 贺强
     * @time   2019-01-18 16:36:21
     * @param  FriendPchatModel $fc FriendPchatModel 实例
     */
    public function public_chat(FriendPchatModel $fc)
    {
        $param = $this->param;
        if (empty($param['room_id'])) {
            $msg = ['status' => 1, 'info' => '房间ID不能为空', 'data' => null];
        } elseif (empty($param['uid'])) {
            $msg = ['status' => 3, 'info' => '说话者ID不能为空', 'data' => null];
        } elseif (empty($param['content'])) {
            $msg = ['status' => 5, 'info' => '说话内容不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid  = $param['uid'];
        $u    = new UserModel();
        $user = $u->getModel(['id' => $uid], ['nickname', 'avatar', 'sex']);
        if (!empty($user)) {
            $param['nickname'] = $user['nickname'];
            $param['avatar']   = $user['avatar'];
            $param['sex']      = $user['sex'];
        }
        $param['addtime'] = time();
        // 添加
        $res = $fc->add($param);
        if (!$res) {
            echo json_encode(['status' => 40, 'info' => '添加失败', 'data' => null]);exit;
        }
        echo json_encode(['status' => 0, 'info' => '添加成功', 'data' => null]);exit;
    }
}
