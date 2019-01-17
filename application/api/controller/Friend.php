<?php
namespace app\api\controller;

use app\common\model\FriendChatModel;
use app\common\model\FriendCommentModel;
use app\common\model\FriendModel;
use app\common\model\FriendMoodModel;
use app\common\model\FriendTopicModel;
use app\common\model\FriendZanModel;

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
        $list = $fm->getList($where, ['id', 'uid', 'nickname', 'avatar', 'content', 'type', 'thumb', 'pic', 'topic', 'zan_count', 'pl_count', 'addtime'], "$page,$pagesize", $order);
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
                        $tps[] = [$t => $topic[$t]];
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
            $msg = ['status' => 3, 'info' => '用户ID不能为空', 'date' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid     = $param['uid'];
        $mood_id = $param['mood_id'];
        $mood    = $fm->getModel(['id' => $mood_id]);
        if ($mood) {
            $ft    = new FriendTopicModel;
            $topic = $ft->getList([], ['id', 'title']);
            $topic = array_column($topic, 'title', 'id');
            $tps   = [];
            if (!empty($mood['topic'])) {
                $topics = explode(',', $mood['topic']);
                foreach ($topics as $t) {
                    $tps[] = [$t => $topic[$t]];
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
                $cos = $fc->getList(['mood_id' => $mood_id], ['id', 'obj_id', 'uid', 'nickname', 'sex', 'content', 'addtime', 'type'], null, 'addtime desc');
                $rpl = array_column($cos, null, 'id');
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
                    $item['reply'] = [];
                    foreach ($cos as $k => &$hf) {
                        if ($hf['type'] === 1) {
                            unset($cos[$k]);
                            continue;
                        }
                        unset($hf['type']);
                        $diff = time() - $hf['addtime'];
                        if ($diff < 60) {
                            $hf['addtime'] = '刚刚';
                        } elseif ($diff < 3600) {
                            $hf['addtime'] = intval($diff / 60) . '分钟前';
                        } elseif ($diff < 86400) {
                            $hf['addtime'] = intval($diff / 3600) . '小时前';
                        } else {
                            $hf['addtime'] = date('Y-m-d H:i:s', $hf['addtime']);
                        }
                        if (!empty($rpl[$hf['obj_id']])) {
                            $rpy = $rpl[$hf['obj_id']];
                            // 属性赋值
                            $hf['ruid']      = $rpy['uid'];
                            $hf['rnickname'] = $rpy['nickname'];
                        }
                        unset($hf['obj_id']);
                        $item['reply'][] = $hf;
                    }
                }
            }
            $mood['comment'] = $list;
        }
        echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $mood]);exit;
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
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $uid1  = intval($param['uid1']);
        $uid2  = intval($param['uid2']);
        $where = "(uid1=$uid1 and uid2=$uid2) or (uid1=$uid2} and uid2=$uid1)";
        $fnd   = $f->getModel($where);
        if (empty($fnd)) {
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
            $list = $f->getList($where, ['uid1', 'nickname1', 'avatar1', 'uid2', 'nickname2', 'avatar2', 'is_friend'], "$page,$pagesize");
            foreach ($list as &$item) {
                // 过滤掉本人只取好友信息
                if ($item['uid1'] === $uid) {
                    unset($item['uid1'], $item['nickname1'], $item['avatar1']);
                    $item['uid']      = $item['uid2'];
                    $item['nickname'] = $item['nickname2'];
                    $item['avatar']   = $item['avatar2'];
                    unset($item['uid2'], $item['nickname2'], $item['avatar2']);
                } elseif ($item['uid2'] === $uid) {
                    unset($item['uid2'], $item['nickname2'], $item['avatar2']);
                    $item['uid']      = $item['uid1'];
                    $item['nickname'] = $item['nickname1'];
                    $item['avatar2']  = $item['avatar1'];
                    unset($item['uid1'], $item['nickname1'], $item['avatar1']);
                }
            }
            if ($self) {
                return $list;
            }
            echo json_encode(['status' => 0, 'info' => '获取成功', 'data' => $list]);exit;
        }
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
            # code...
        }
    }

    public function test()
    {
        $res = getVideoCover('https://hkqgg.cn/uploads/cli/img/2019/01/15/1547524052443.mp4', 11, true);
        var_dump($res);exit;
    }
}
