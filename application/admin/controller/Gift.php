<?php
namespace app\admin\controller;

use app\common\model\GiftModel;

/**
 * Gift-控制器
 * @author 贺强
 * @time   2019-01-10 12:28:40
 */
class Gift extends \think\Controller
{
    public function add(GiftModel $g)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['name'])) {
                return ['status' => 1, 'info' => '礼物名称不有为空'];
            } elseif (empty($param['logo'])) {
                return ['status' => 3, 'info' => '礼物图标不能为空'];
            } elseif (empty($param['price'])) {
                return ['status' => 5, 'info' => '礼物价格不能为空'];
            }
            $param['addtime'] = time();
            // 添加
            $res = $g->add($param);
            if (!$res) {
                return ['status' => 40, 'info' => '添加失败'];
            }
            return ['status' => 0, 'info' => '添加成功'];
        } else {
            $time  = time();
            $types = config('GIFT_TYPE');
            return $this->fetch('add', ['time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time), 'types' => $types]);
        }
    }

    /**
     * 删除礼物
     * @author 贺强
     * @time   2019-01-10 15:11:43
     * @param  GiftModel $g GiftModel 实例
     */
    public function del(GiftModel $g)
    {
        $ids = $this->request->post('ids', 0);
        if (empty($ids) || preg_match('/^0[\,\d+]+$/', $ids)) {
            return ['status' => 1, 'info' => '非法参数'];
        }
        $res = $g->del_gift($ids);
        if (!$res) {
            return ['status' => 4, 'info' => '删除失败'];
        }
        return ['status' => 0, 'info' => '删除成功'];
    }

    /**
     * 修改礼物
     * @author 贺强
     * @time   2019-01-10 15:12:51
     * @param  GiftModel $g GiftModel 实例
     */
    public function edit(GiftModel $g)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id'])) {
                return ['status' => 1, 'info' => '非法参数'];
            } elseif (empty($param['type'])) {
                return ['status' => 3, 'info' => '礼物类别不能为空'];
            } elseif (empty($param['name'])) {
                return ['status' => 5, 'info' => '礼物名称不能为空'];
            } elseif (empty($param['logo'])) {
                return ['status' => 7, 'info' => '礼物图片不能为空'];
            } elseif (empty($param['price'])) {
                return ['status' => 9, 'info' => '礼物价格不能为空'];
            }
            $res = $g->modify($param, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 40, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        } else {
            $id = $this->request->get('id');
            if (empty($id) || !preg_match('/^\d+$/', $id)) {
                echo '非法参数';exit;
            }
            $gift = $g->getModel(['id' => $id]);
            if (!empty($gift['logo'])) {
                $gift['logo1'] = config('WEBSITE') . $gift['logo'];
            }
            $types = config('GIFT_TYPE');
            $time  = time();
            return $this->fetch('edit', ['gift' => $gift, 'time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time), 'types' => $types]);
        }
    }

    /**
     * 修改礼物排序
     * @author 贺强
     * @time   2019-01-10 15:11:34
     * @param  GiftModel $g GiftModel 实例
     */
    public function editsort(GiftModel $g)
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
     * 礼物列表
     * @author 贺强
     * @time   2019-01-10 14:59:21
     * @param  GiftModel $g GiftModel 实例
     */
    public function lists(GiftModel $g)
    {
        $where = [];
        // 分页参数
        $page     = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', config('PAGESIZE'));
        $list     = $g->getList($where, true, "$page,$pagesize", 'sort');
        foreach ($list as &$item) {
            if (!empty($item['logo']) && strpos($item['logo'], 'https://') === false) {
                $item['logo'] = config('WEBSITE') . $item['logo'];
            }
            if (!empty($item['addtime'])) {
                $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
            }
        }
        $count = $g->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('list', ['list' => $list, 'pages' => $pages]);
    }
}
