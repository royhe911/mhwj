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
        $list = $fm->getList($where, ['id', 'uid', 'content', 'pic', 'topic1', 'topic2', 'topic3', 'zan_count', 'pl_count', 'addtime']);
        if ($list) {
            $uids  = array_column($list, 'uid');
            $u     = new UserModel();
            $users = $u->getList(['id' => ['in', $uids]], ['id', 'nickname', 'avatar']);
            $users = array_column($users, null, 'id');
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
                if (!empty($users[$item['uid']])) {
                    $user = $users[$item['uid']];
                    // 属性赋值
                    $item['nickname'] = $user['nickname'];
                    $item['avatar']   = $user['avatar'];
                } else {
                    $item['nickname'] = '';
                    $item['avatar']   = '';
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
}
