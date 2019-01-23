<?php
namespace app\admin\controller;

use app\common\model\TCircleModel;
use app\common\model\TTopicModel;

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
}
