<?php
namespace app\api\controller;

use app\common\model\FriendCommentModel;
use app\common\model\FriendMoodModel;
use app\common\model\FriendTopicModel;
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
        $param['addtime'] = time();
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
        // 添加
        $res = $fc->add($param);
        if (!$res) {
            echo json_encode(['status' => 40, 'info' => '评论失败', 'data' => null]);exit;
        } else {
            echo json_encode(['status' => 0, 'info' => '评论成功', 'data' => null]);exit;
        }
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
            $msg = ['status' => 3, 'info' => '用户ID不空', 'data' => null];
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
        $where = [];
        $order = 'addtime desc';
        $param = $this->param;
        if (!empty($param['is_recommend'])) {
            $where['is_recommend'] = 1;
            $order                 = 'sort';
        }
        $page = 1;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        $pagesize = 10;
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $list = $fm->getList($where, ['id', 'uid', 'nickname', 'avatar', 'content', 'pic', 'topic1', 'topic2', 'topic3', 'zan_count', 'pl_count', 'addtime']);
        if ($list) {
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
                if (!empty($item['pic'])) {
                    $pics = explode($item['pic'], ',');
                    foreach ($pics as &$pic) {
                        if (strpos($pic, 'http://') === false && strpos($pic, 'https://') === false) {
                            $pic = config('WEBSITE') . $pic;
                        }
                    }
                    $item['pic'] = $pics;
                }
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
        if (empty($param['moodid'])) {
            $msg = ['status' => 1, 'info' => 'ID不能为空', 'data' => null];
        }
        if (!empty($msg)) {
            echo json_encode($msg);exit;
        }
        $moodid = $param['moodid'];
        $mood   = $fm->getModel(['id' => $moodid]);
        if ($mood) {
            $ft    = new FriendTopicModel;
            $topic = $ft->getList([], ['id', 'title']);
            $topic = array_column($topic, 'title', 'id');
            foreach ($topic as &$tc) {
                if (strpos($tc, '#') === false) {
                    $tc = '#' . $tc;
                }
            }
            if (!empty($mood['topic1']) && !empty($topic[$mood['topic1']])) {
                $mood['topic1'] = $topic[$mood['topic1']];
            }
            if (!empty($mood['topic2']) && !empty($topic[$mood['topic2']])) {
                $mood['topic2'] = $topic[$mood['topic2']];
            }
            if (!empty($mood['topic3']) && !empty($topic[$mood['topic3']])) {
                $mood['topic3'] = $topic[$mood['topic3']];
            }
            $u    = new UserModel();
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
            $list = $fc->getList(['mood_id' => $param['moodid'], 'type' => 1], ['id', 'nickname', 'avatar', 'sex', 'content', 'zan_count', 'addtime'], null, 'addtime desc');
            if ($list) {
                $cos = $fc->getList(['mood_id' => $moodid], ['id', 'obj_id', 'uid', 'nickname', 'sex', 'content', 'zan_count', 'addtime', 'type'], null, 'addtime desc');
                $rpl = array_column($cos, null, 'id');
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
                    foreach ($cos as $k => &$hf) {
                        if ($hf['type'] === 1) {
                            unset($cos[$k]);
                            continue;
                        }
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
                            $hf['rid']       = $rpy['uid'];
                            $hf['rnickname'] = $rpy['nickname'];
                        }
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
        $zw   = ['id' => $id];
        $type = intval($param['type']);
        switch ($type) {
            case 1:
                $m  = new FriendMoodModel();
                $cw = ['mood_id' => $id];
                break;
            case 2:
                $m = new FriendCommentModel();
                break;
            default:
                $m = new FriendCommentModel();
                break;
        }
        $res = $m->delById($id);
        if (!$res) {
            echo json_encode(['status' => 40, 'info' => '删除失败', 'data' => null]);exit;
        }
        if (!empty($cw)) {
            $fc = new FriendCommentModel();
            $fc->delByWhere($cw);
        }
        echo json_encode(['status' => 0, 'info' => '删除成功', 'data' => null]);exit;
    }
}
