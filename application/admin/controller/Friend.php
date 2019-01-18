<?php
namespace app\admin\controller;

use app\common\model\FriendMoodModel;
use app\common\model\FriendTopicModel;
use app\common\model\UserModel;

/**
 * Friend-控制器
 * @author 贺强
 * @time   2019-01-11 11:17:43
 */
class Friend extends \think\Controller
{
    /**
     * 添加主题
     * @author 贺强
     * @time   2019-01-11 11:22:11
     * @param  FriendTopicModel $ft FriendTopicModel 实例
     */
    public function addtopic(FriendTopicModel $ft)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['title'])) {
                return ['status' => 1, 'info' => '主题不能为空'];
            }
            // 添加
            $res = $ft->add($param);
            if (!$res) {
                return ['status' => 40, 'info' => '添加失败'];
            }
            return ['status' => 0, 'info' => '添加成功'];
        } else {
            return $this->fetch('addtopic');
        }
    }

    /**
     * 主题启用/禁用/删除
     * @author 贺强
     * @time   2019-01-11 12:13:32
     * @param  FriendTopicModel $ft FriendTopicModel 实例
     */
    public function operate(FriendTopicModel $ft)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            $type  = $param['type'];
            $ids   = $param['ids'];
            if ($type === 'del' || $type === 'delAll') {
                $res = $ft->delByWhere(['id' => ['in', $ids]]);
            } else {
                $val = 1;
                if ($type === 'qx' || $type === 'qxAll') {
                    $val = 44;
                }
                $res = $ft->modifyField('status', $val, ['id' => ['in', $ids]]);
            }
            if (!$res) {
                return ['status' => 4, 'info' => '失败'];
            }
            return ['status' => 0, 'info' => '成功'];
        }
    }

    /**
     * 修改主题排序
     * @author 贺强
     * @time   2019-01-11 12:11:27
     * @param  FriendTopicModel $ft FriendTopicModel 实例
     */
    public function editsort(FriendTopicModel $ft)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $res = $ft->modify($param, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 2, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        }
    }

    /**
     * 修改主题
     * @author 贺强
     * @time   2019-01-11 12:12:23
     * @param  FriendTopicModel $ft FriendTopicModel 实例
     */
    public function edittitle(FriendTopicModel $ft)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $res = $ft->modify($param, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 2, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        }
    }

    /**
     * 主题列表
     * @author 贺强
     * @time   2019-01-11 11:31:05
     * @param  FriendTopicModel $ft FriendTopicModel 实例
     */
    public function topic_list(FriendTopicModel $ft)
    {
        $where = [];
        // 分页参数
        $page     = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', config('PAGESIZE'));
        $list     = $ft->getList($where, true, "$page,$pagesize", 'sort');
        foreach ($list as &$item) {
            if ($item['status'] === 1) {
                $item['status_txt'] = '启用';
            } elseif ($item['status'] === 44) {
                $item['status_txt'] = '禁用';
            }
            if (!empty($item['addtime'])) {
                $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
            }
        }
        $count = $ft->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('topic', ['list' => $list, 'pages' => $pages]);
    }

    /**
     * 发布心情
     * @author 贺强
     * @time   2019-01-17 14:33:13
     * @param  FriendMoodModel $fm FriendMoodModel 实例
     */
    public function addmood(FriendMoodModel $fm)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            $type  = 1;
            if (!empty($param['type'])) {
                $tpe = $param['type'];
                if ($tpe === 'mp4' || $tpe === 'avi' || $tpe === 'mov' || $tpe === 'wmv' || $tpe === '3gp') {
                    $type = 2;
                }
            }
            $param['type']         = $type;
            $param['origin']       = 10;
            $param['is_recommend'] = 1;
            $param['addtime']      = time();
            // 添加
            $res = $fm->add($param);
            if ($res) {
                return ['status' => 0, 'info' => '添加成功'];
            }
            return ['status' => 4, 'info' => '添加失败'];
        } else {
            $u     = new UserModel();
            $users = $u->getList(['type' => 3], ['id', 'nickname', 'avatar', 'sex']);
            $time  = time();
            return $this->fetch('addmood', ['time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time), 'users' => $users]);
        }
    }

    /**
     * 心情推荐/取消推荐/删除
     * @author 贺强
     * @time   2019-01-17 15:56:24
     * @param  FriendMoodModel $fm FriendMoodModel 实例
     */
    public function operatem(FriendMoodModel $fm)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            $type  = $param['type'];
            $ids   = $param['ids'];
            if ($type === 'del' || $type === 'delAll') {
                $res = $fm->delByWhere(['id' => ['in', $ids]]);
            } else {
                $val = 1;
                if ($type === 'qx' || $type === 'qxAll') {
                    $val = 0;
                }
                $res = $fm->modifyField('is_recommend', $val, ['id' => ['in', $ids]]);
            }
            if (!$res) {
                return ['status' => 4, 'info' => '失败'];
            }
            return ['status' => 0, 'info' => '成功'];
        }
    }

    /**
     * 修改主题排序
     * @author 贺强
     * @time   2019-01-11 12:11:27
     * @param  FriendMoodModel $fm FriendMoodModel 实例
     */
    public function editmsort(FriendMoodModel $fm)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $res = $fm->modify($param, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 2, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        }
    }

    /**
     * 心情列表
     * @author 贺强
     * @time   2019-01-17 15:34:54
     * @param  FriendMoodModel $fm FriendMoodModel 实例
     */
    public function moodlist(FriendMoodModel $fm)
    {
        $where = [];
        $param = $this->request->get();
        if (!empty($param['uid'])) {
            $where['uid'] = $param['uid'];
        } else {
            $param['uid'] = '';
        }
        if (isset($param['is_recommend']) && $param['is_recommend'] !== '') {
            $where['is_recommend'] = $param['is_recommend'];
        } else {
            $param['is_recommend'] = '';
        }
        if (!empty($param['nickname'])) {
            $where['nickname'] = ['like', "%{$param['nickname']}%"];
        } else {
            $param['nickname'] = '';
        }
        // 分页参数
        $page = 1;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        $pagesize = config('PAGESIZE');
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $list = $fm->getList($where, true, "$page,$pagesize", 'sort,addtime desc');
        foreach ($list as &$item) {
            if ($item['origin']) {
                $item['origin'] = '官方';
            } else {
                $item['origin'] = '用户';
            }
            if (!empty($item['addtime'])) {
                $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
            }
        }
        $count = $fm->getCount($where);
        $pages = ceil($count / $pagesize);
        $u     = new UserModel();
        $users = $u->getList(['type' => 3], ['id', 'nickname', 'avatar', 'sex']);
        return $this->fetch('moodlist', ['list' => $list, 'pages' => $pages, 'users' => $users, 'param' => $param]);
    }
}
