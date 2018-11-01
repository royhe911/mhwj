<?php
namespace app\admin\controller;

use app\common\model\NoticeModel;

/**
 * Set-控制器
 * @author 贺强
 * @time   2018-10-26 11:29:15
 */
class Set extends \think\Controller
{
    /**
     * 添加轮播图
     * @author 贺强
     * @time   2018-10-26 12:17:39
     * @param  NoticeModel $n NoticeModel 实例
     */
    public function add(NoticeModel $n)
    {
        // 判断是否有权限访问或操作
        $admin = $this->is_valid(strtolower(basename(get_class())) . '_' . strtolower(__FUNCTION__));
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['name']) || empty($param['url'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $param['addtime'] = time();
            $res              = $n->add($param);
            if (!$res) {
                return ['status' => 4, 'info' => '添加失败'];
            }
            return ['status' => 0, 'info' => '添加成功'];
        } else {
            $time = time();
            return $this->fetch('add', ['time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time)]);
        }
    }

    /**
     * 操作轮播图
     * @author 贺强
     * @time   2018-10-26 11:56:25
     * @param  NoticeModel $n NoticeModel 实例
     * @return bool           返回操作结果
     */
    public function del(NoticeModel $n)
    {
        $ids = $this->request->post('ids');
        if (!preg_match('/^0[\,\d+]+$/', $ids)) {
            return ['status' => 1, 'info' => '非法参数'];
        }
        $action = $this->request->post('action');
        if ($action === 'del' || $action === 'delAll') {
            $res = $n->modifyField('is_delete', 1, ['id' => ['in', $ids]]);
            if (!$res) {
                return ['status' => 4, 'info' => '删除失败'];
            }
            return ['status' => 0, 'info' => '删除成功'];
        }
        return ['status' => 2, 'info' => '非法操作'];
    }

    /**
     * 修改轮播图
     * @author 贺强
     * @time   2018-11-01 15:38:35
     * @param  NoticeModel $n NoticeModel 实例
     */
    public function edit(NoticeModel $n)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id']) || empty($param['name']) || empty($param['url'])) {
                return ['status' => 1, 'info' => '参数缺失'];
            }
            $res = $n->modify($param, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 4, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        } else {
            $id     = $this->request->get('id');
            $notice = $n->getModel(['id' => $id]);
            $time   = time();
            return $this->fetch('edit', ['notice' => $notice, 'time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time)]);
        }
    }

    /**
     * 轮播图列表
     * @author 贺强
     * @time   2018-10-26 11:34:31
     * @param  NoticeModel $n NoticeModel 实例
     * @return list           返回轮播图集合
     */
    public function lists(NoticeModel $n)
    {
        $where = ['status' => 0, 'is_delete' => 0];
        $field = '`id`,`name`,`url`,`sort`,`status`,`addtime`';
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $n->getList($where, $field, "$page,$pagesize", 'sort');
        foreach ($list as &$item) {
            if (!empty($item['addtime'])) {
                $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
            }
            if ($item['status'] === 0) {
                $item['status_txt'] = '正常';
            } else {
                $item['status_txt'] = '';
            }
        }
        $count = $n->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('list', ['list' => $list, 'pages' => $pages]);
    }
}
