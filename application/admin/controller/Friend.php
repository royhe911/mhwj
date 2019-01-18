<?php
namespace app\admin\controller;

use app\common\model\FriendMoodModel;
use app\common\model\FriendProomModel;
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
     * 修改心情排序
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
     * 修改心情
     * @author 贺强
     * @time   2019-01-18 10:43:15
     * @param  FriendMoodModel $fm FriendMoodModel 实例
     */
    public function editmood(FriendMoodModel $fm)
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
            $param['type'] = $type;
            // 修改
            $res = $fm->modify($param, ['id' => $param['id']]);
            if (!$res) {
                return ['status' => 4, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        } else {
            $u     = new UserModel();
            $users = $u->getList(['type' => 3], ['id', 'nickname', 'avatar', 'sex']);
            $id    = $this->request->get('id');
            $mood  = $fm->getModel(['id' => $id]);
            if (!empty($mood['pic']) && strpos($mood['pic'], 'https://') === false && strpos($mood['pic'], 'http://') === false) {
                $mood['url'] = config('WEBSITE') . $mood['pic'];
            }
            if ($mood['type'] === 1) {
                $mood['tpe'] = 'jpg';
            } else {
                $mood['tpe'] = 'mp4';
            }
            $time = time();
            return $this->fetch('editmood', ['time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time), 'mood' => $mood, 'users' => $users]);
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

    /**
     * 添加群聊房间
     * @author 贺强
     * @time   2019-01-18 17:34:59
     * @param  FriendProomModel $fp FriendProomModel 实例
     */
    public function addproom(FriendProomModel $fp)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            // 添加时间
            $param['addtime'] = time();
            // 添加
            $res = $fp->add($param);
            if (!$res) {
                return ['status' => 4, 'info' => '添加失败'];
            }
            return ['status' => 0, 'info' => '添加成功'];
        } else {
            return $this->fetch('addproom');
        }
    }

    /**
     * 删除群聊房间
     * @author 贺强
     * @time   2019-01-18 19:21:03
     * @param  FriendProomModel $fp FriendProomModel 实例
     */
    public function delproom(FriendProomModel $fp)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            $ids   = $param['ids'];
            $res   = $fp->delByWhere(['id' => ['in', $ids]]);
            if (!$res) {
                return ['status' => 4, 'info' => '失败'];
            }
            return ['status' => 0, 'info' => '成功'];
        }
    }

    /**
     * 修改群聊房间排序
     * @author 贺强
     * @time   2019-01-18 19:18:08
     * @param  FriendProomModel $fp FriendProomModel 实例
     */
    public function editpsort(FriendProomModel $fp)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $res = $fp->modify($param, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 2, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        }
    }

    /**
     * 群聊房间
     * @author 贺强
     * @time   2019-01-18 19:02:19
     * @param  FriendProomModel $fp FriendProomModel 实例
     */
    public function get_proom(FriendProomModel $fp)
    {
        $where = [];
        $param = $this->request->get();
        // 分页参数
        $page = 1;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        $pagesize = config('PAGESIZE');
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $list = $fp->getList($where, true, "$page,$pagesize", 'addtime desc');
        foreach ($list as &$item) {
            if (!empty($item['addtime'])) {
                $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
            }
        }
        $count = $fp->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('proom', ['list' => $list, 'pages' => $pages]);
    }
}
