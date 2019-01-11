<?php
namespace app\admin\controller;

use app\common\model\FriendTopicModel;

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
                if ($type === 'forbid' || $type === 'forbidAll') {
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
}
