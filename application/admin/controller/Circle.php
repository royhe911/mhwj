<?php
namespace app\admin\controller;

use app\common\model\TCircleModel;
use app\common\model\TDynamicCommentModel;
use app\common\model\TDynamicModel;
use app\common\model\TGameModel;
use app\common\model\TTopicModel;
use app\common\model\TUserModel;

/**
 * 圈子-控制器
 * @author 贺强
 * @time   2019-01-23 09:20:28
 */
class Circle extends \think\Controller
{
    /**
     * 添加话题
     * @author 贺强
     * @time   2019-01-23 09:22:49
     * @param  TTopicModel $t TTopicModel 实例
     */
    public function addtopic(TTopicModel $t)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['title'])) {
                return ['status' => 1, 'info' => '主题不能为空'];
            }
            // 添加
            $res = $t->add($param);
            if (!$res) {
                return ['status' => 40, 'info' => '添加失败'];
            }
            return ['status' => 0, 'info' => '添加成功'];
        } else {
            return $this->fetch('addtopic');
        }
    }

    /**
     * 话题启用/禁用/删除
     * @author 贺强
     * @time   2019-01-23 09:26:21
     * @param  TTopicModel $t [description]
     */
    public function operate(TTopicModel $t)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            $type  = $param['type'];
            $ids   = $param['ids'];
            if ($type === 'del' || $type === 'delAll') {
                $res = $t->delByWhere(['id' => ['in', $ids]]);
            } else {
                $val = 1;
                if ($type === 'forbid' || $type === 'forbidAll') {
                    $val = 44;
                }
                $res = $t->modifyField('status', $val, ['id' => ['in', $ids]]);
            }
            if (!$res) {
                return ['status' => 4, 'info' => '失败'];
            }
            return ['status' => 0, 'info' => '成功'];
        }
    }

    /**
     * 修改话题排序
     * @author 贺强
     * @time   2019-01-23 09:25:23
     * @param  TTopicModel $t TTopicModel 实例
     */
    public function editsort(TTopicModel $t)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $res = $t->modify($param, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 2, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        }
    }

    /**
     * 修改话题
     * @author 贺强
     * @time   2019-01-23 09:25:06
     * @param  TTopicModel $t TTopicModel 实例
     */
    public function edittitle(TTopicModel $t)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $res = $t->modify($param, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 2, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        }
    }

    /**
     * 话题列表
     * @author 贺强
     * @time   2019-01-23 09:27:24
     * @param  TTopicModel $t TTopicModel 实例
     */
    public function topic_list(TTopicModel $t)
    {
        $where = [];
        // 分页参数
        $page     = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', config('PAGESIZE'));
        $list     = $t->getList($where, true, "$page,$pagesize", 'sort');
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
        $count = $t->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('topic', ['list' => $list, 'pages' => $pages]);
    }

    /**
     * 添加圈子
     * @author 贺强
     * @time   2019-01-23 09:44:13
     * @param  TCircleModel $t TCircleModel 实例
     */
    public function add(TCircleModel $t)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['name'])) {
                return ['status' => 1, 'info' => '圈子名称不能为空'];
            }
            // 添加
            $res = $t->add($param);
            if (!$res) {
                return ['status' => 40, 'info' => '添加失败'];
            }
            return ['status' => 0, 'info' => '添加成功'];
        } else {
            return $this->fetch('add');
        }
    }

    /**
     * 删除
     * @author 贺强
     * @time   2019-01-23 09:46:53
     * @param  TCircleModel $t TCircleModel 实例
     */
    public function del(TCircleModel $t)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            $ids   = $param['ids'];
            $res   = $t->delByWhere(['id' => ['in', $ids]]);
            if (!$res) {
                return ['status' => 4, 'info' => '失败'];
            }
            return ['status' => 0, 'info' => '成功'];
        }
    }

    /**
     * 修改圈子排序
     * @author 贺强
     * @time   2019-01-23 09:25:23
     * @param  TCircleModel $t TCircleModel 实例
     */
    public function editcsort(TCircleModel $t)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $res = $t->modify($param, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 2, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        }
    }

    /**
     * 修改话题
     * @author 贺强
     * @time   2019-01-23 09:25:06
     * @param  TCircleModel $t TCircleModel 实例
     */
    public function editname(TCircleModel $t)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $res = $t->modify($param, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 2, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        }
    }

    /**
     * 话题列表
     * @author 贺强
     * @time   2019-01-23 09:27:24
     * @param  TCircleModel $t TCircleModel 实例
     */
    public function lists(TCircleModel $t)
    {
        $where = [];
        // 分页参数
        $page     = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', config('PAGESIZE'));
        $list     = $t->getList($where, true, "$page,$pagesize", 'sort');
        foreach ($list as &$item) {
            if (!empty($item['addtime'])) {
                $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
            }
        }
        $count = $t->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('list', ['list' => $list, 'pages' => $pages]);
    }

    /**
     * 发布动态
     * @author 贺强
     * @time   2019-01-24 12:16:45
     * @param  TDynamicModel $d TDynamicModel 实例
     */
    public function adddynamic(TDynamicModel $d)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['uid'])) {
                return ['status' => 1, 'info' => '用户ID不能为空'];
            } elseif (empty($param['content'])) {
                return ['status' => 3, 'info' => '动态内容不能为空'];
            }
            if ($param['type'] == 'jpg') {
                $param['type'] = 1;
            } elseif ($param['type'] == 'mp4') {
                $param['type'] = 2;
            }
            $u    = new TUserModel();
            $uid  = $param['uid'];
            $user = $u->getModel(['id' => $uid], ['nickname', 'avatar', 'sex', 'circle']);
            if (!empty($user)) {
                $param['nickname'] = $user['nickname'];
                $param['avatar']   = $user['avatar'];
                $param['sex']      = $user['sex'];
            }
            $param['origin']  = 1;
            $param['is_open'] = 1;
            $param['addtime'] = time();
            // 添加
            $res = $d->add($param);
            if (!$res) {
                return ['status' => 4, 'info' => '发布失败'];
            }
            return ['status' => 0, 'info' => '发布成功'];
        } else {
            $u     = new TUserModel();
            $users = $u->getList(['addtime' => 1548303058], ['id', 'nickname', 'avatar']);
            $time  = time();
            return $this->fetch('adddynamic', ['time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time), 'users' => $users]);
        }
    }

    /**
     * 修改动态
     * @author 贺强
     * @time   2019-01-24 14:36:19
     * @param  TDynamicModel $d TDynamicModel 实例
     */
    public function modifydy(TDynamicModel $d)
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
            $res = $d->modify($param, ['id' => $param['id']]);
            if (!$res) {
                return ['status' => 4, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        } else {
            $u       = new TUserModel();
            $users   = $u->getList(['addtime' => 1548303058], ['id', 'nickname', 'avatar', 'sex']);
            $id      = $this->request->get('id');
            $dynamic = $d->getModel(['id' => $id]);
            if (!empty($dynamic['pic']) && strpos($dynamic['pic'], 'https://') === false && strpos($dynamic['pic'], 'http://') === false) {
                $dynamic['url'] = config('WEBSITE') . $dynamic['pic'];
            }
            if ($dynamic['type'] === 1) {
                $dynamic['tpe'] = 'jpg';
            } else {
                $dynamic['tpe'] = 'mp4';
            }
            $time = time();
            return $this->fetch('editdy', ['time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time), 'dynamic' => $dynamic, 'users' => $users]);
        }
    }

    /**
     * 修改动态排序
     * @author 贺强
     * @time   2019-01-24 14:47:02
     * @param  TDynamicModel $d TDynamicModel 实例
     */
    public function editmsort(TDynamicModel $d)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $res = $d->modify($param, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 2, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        }
    }

    /**
     * 操作动态
     * @author 贺强
     * @time   2019-01-24 14:51:26
     * @param  TDynamicModel $d TDynamicModel 实例
     */
    public function operatem(TDynamicModel $d)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            $type  = $param['type'];
            $ids   = $param['ids'];
            if ($type === 'del' || $type === 'delAll') {
                $res = $d->delByWhere(['id' => ['in', $ids]]);
            } else {
                $val = 1;
                if ($type === 'qx' || $type === 'qxAll') {
                    $val = 0;
                }
                $res = $d->modifyField('is_recommend', $val, ['id' => ['in', $ids]]);
            }
            if (!$res) {
                return ['status' => 4, 'info' => '失败'];
            }
            return ['status' => 0, 'info' => '成功'];
        }
    }

    /**
     * 动态列表
     * @author 贺强
     * @time   2019-01-24 14:34:51
     * @param  TDynamicModel $d TDynamicModel 实例
     */
    public function dylist(TDynamicModel $d)
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
        $list = $d->getList($where, true, "$page,$pagesize", 'addtime desc');
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
        $count = $d->getCount($where);
        $pages = ceil($count / $pagesize);
        $u     = new TUserModel();
        $users = $u->getList(['addtime' => 1548303058], ['id', 'nickname', 'avatar', 'sex']);
        return $this->fetch('dylist', ['list' => $list, 'pages' => $pages, 'users' => $users, 'param' => $param]);
    }

    /**
     * 评论动态
     * @author 贺强
     * @time   2019-01-24 15:08:09
     * @param  TDynamicCommentModel $dc TDynamicCommentModel 实例
     */
    public function comment(TDynamicCommentModel $dc, TDynamicModel $d)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['uid'])) {
                return ['status' => 1, 'info' => '请选择评论者'];
            } elseif (empty($param['content'])) {
                return ['status' => 3, 'info' => '评论内容不能为空'];
            }
            $param['obj_id'] = $param['did'];
            // 添加
            $res = $dc->add($param);
            if (!$res) {
                return ['status' => 4, 'info' => '评论失败'];
            }
            $d->increment('pl_count', ['id' => $param['did']]);
            return ['status' => 0, 'info' => '评论成功'];
        } else {
            $u     = new TUserModel();
            $users = $u->getList(['addtime' => 1548303058], ['id', 'nickname', 'avatar', 'sex']);
            $id    = $this->request->get('id');
            $dy    = $d->getModel(['id' => $id], ['content']);
            return $this->fetch('comment', ['did' => $id, 'dycontent' => $dy['content'], 'users' => $users]);
        }
    }

    /**
     * 添加游戏
     * @author 贺强
     * @time   2019-01-25 19:21:04
     * @param  TGameModel $g TGameModel 实例
     */
    public function addgame(TGameModel $g)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['name'])) {
                return ['status' => 1, 'info' => '游戏名称'];
            }
            // 添加
            $res = $g->add($param);
            if (!$res) {
                return ['status' => 40, 'info' => '添加失败'];
            }
            return ['status' => 0, 'info' => '添加成功'];
        } else {
            $time = time();
            return $this->fetch('addgame', ['time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time)]);
        }
    }

    /**
     * 操作游戏
     * @author 贺强
     * @time   2019-01-25 19:10:39
     * @param  TGameModel $g TGameModel 实例
     */
    public function operateg(TGameModel $g)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            $type  = $param['type'];
            $ids   = $param['ids'];
            if ($type === 'del' || $type === 'delAll') {
                $res = $g->delByWhere(['id' => ['in', $ids]]);
            } else {
                $val = 1;
                if ($type === 'disable' || $type === 'disableAll') {
                    $val = 0;
                }
                $res = $g->modifyField('status', $val, ['id' => ['in', $ids]]);
            }
            if (!$res) {
                return ['status' => 4, 'info' => '失败'];
            }
            return ['status' => 0, 'info' => '成功'];
        }
    }

    /**
     * 修改游戏
     * @author 贺强
     * @time   2019-01-25 19:43:33
     * @param  TGameModel $g TGameModel 实例
     */
    public function editgame(TGameModel $g)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id'])) {
                return ['status' => 1, 'info' => '非法参数'];
            } elseif (empty($param['name'])) {
                return ['status' => 5, 'info' => '游戏名称不能为空'];
            } elseif (empty($param['logo'])) {
                return ['status' => 7, 'info' => '游戏图片不能为空'];
            }
            $res = $g->modify($param, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 40, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        } else {
            $id = $this->request->get('id');
            if (empty($id)) {
                echo '非法参数';exit;
            }
            $game = $g->getModel(['id' => $id]);
            if (!empty($game['logo'])) {
                $game['logo1'] = config('WEBSITE') . $game['logo'];
            } else {
                $game['logo1'] = '';
            }
            $types = config('GIFT_TYPE');
            $time  = time();
            return $this->fetch('editgame', ['game' => $game, 'time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time), 'types' => $types]);
        }
    }

    /**
     * 修改游戏排序
     * @author 贺强
     * @time   2019-01-25 19:18:20
     * @param  TGameModel $g TGameModel 实例
     */
    public function editgsort(TGameModel $g)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $res = $g->modify($param, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 2, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        }
    }

    /**
     * 游戏列表
     * @author 贺强
     * @time   2019-01-25 18:39:37
     * @param  TGameModel $g TGameModel 实例
     */
    public function gamelist(TGameModel $g)
    {
        $param = $this->request->get();
        $page  = 1;
        if (!empty($param['page'])) {
            $pagesize = $param['page'];
        }
        $pagesize = 10;
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $list = $g->getList([], true, "$page,$pagesize", 'sort');
        foreach ($list as &$item) {
            if (!empty($item['logo']) && strpos($item['logo'], 'http://') === false && strpos($item['logo'], 'https://') === false) {
                $item['logo'] = config('WEBSITE') . $item['logo'];
            }
            if ($item['status']) {
                $item['status_txt'] = '可用';
            } else {
                $item['status_txt'] = '不可用';
            }
        }
        $count = $g->getCount();
        $pages = ceil($count / $pagesize);
        return $this->fetch('gamelist', ['list' => $list, 'pages' => $pages]);
    }

    /**
     * 用户列表
     * @author 贺强
     * @time   2019-01-29 09:19:47
     * @param  TUserModel $u TUserModel 实例
     */
    public function users(TUserModel $u)
    {
        $param = $this->request->get();
        $page  = 1;
        if (!empty($param['page'])) {
            $page = $param['page'];
        }
        $pagesize = 10;
        if (!empty($param['pagesize'])) {
            $pagesize = $param['pagesize'];
        }
        $where = [];
        if (!empty($param['nickname'])) {
            $where = ['nickname' => ['like', "%{$param['nickname']}%"]];
        } else {
            $param['nickname'] = '';
        }
        $list = $u->getList($where, true, "$page,$pagesize", 'addtime desc');
        foreach ($list as &$item) {
            if ($item['status'] === 1) {
                $item['status_txt'] = '启用';
            } else {
                $item['status_txt'] = '禁用';
            }
            if ($item['sex'] === 1) {
                $item['sex'] = '男';
            } elseif ($item['sex'] === 2) {
                $item['sex'] = '女';
            } else {
                $item['sex'] = '保密';
            }
            if (!empty($item['addtime'])) {
                $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
            }
        }
        $count = $u->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('users', ['list' => $list, 'pages' => $pages, 'param' => $param]);
    }

    /**
     * 操作用户
     * @author 贺强
     * @time   2019-01-29 09:44:15
     * @param  TUserModel $u TUserModel 实例
     */
    public function operateu(TUserModel $u)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            $type  = $param['type'];
            $ids   = $param['ids'];
            if ($type === 'del' || $type === 'delAll') {
                $res = $u->delByWhere(['id' => ['in', $ids]]);
            } else {
                $val = 1;
                if ($type === 'disable' || $type === 'disableAll') {
                    $val = 44;
                }
                $res = $u->modifyField('status', $val, ['id' => ['in', $ids]]);
            }
            if (!$res) {
                return ['status' => 4, 'info' => '失败'];
            }
            return ['status' => 0, 'info' => '成功'];
        }
    }

}
