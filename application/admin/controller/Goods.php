<?php
namespace app\admin\controller;

use app\common\model\GoodsModel;

/**
 * 商品-控制器
 * @author 贺强
 * @time   2018-12-10 16:45:30
 */
class Goods extends \think\Controller
{
    /**
     * 添加商品
     * @author 贺强
     * @time   2018-12-10 16:56:43
     * @param  GoodsModel $g GoodsModel 实例
     */
    public function add(GoodsModel $g)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['name'])) {
                return ['status' => 1, 'info' => '商品名称不能为空'];
            }
            if (empty($param['url'])) {
                return ['status' => 3, 'info' => '商品图片不能为空'];
            }
            if (empty($param['price'])) {
                return ['status' => 5, 'info' => '商品价格不能为空'];
            }
            if (empty($param['min_knife_num'])) {
                return ['status' => 7, 'info' => '所需最低刀数不能为空'];
            }
            if (empty($param['max_knife_num'])) {
                return ['status' => 9, 'info' => '所需最多刀数不能为空'];
            }
            $param['code']    = get_millisecond();
            $param['addtime'] = time();
            $res              = $g->add($param);
            if (!$res) {
                return ['status' => 44, 'info' => '添加失败'];
            }
            return ['status' => 0, 'info' => '添加成功'];
        } else {
            $time = time();
            return $this->fetch('add', ['time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time)]);
        }
    }

    /**
     * 操作商品
     * @author 贺强
     * @time   2018-12-10 17:25:56`
     * @param  GoodsModel $g GoodsModel 实例
     */
    public function operate(GoodsModel $g)
    {
        $ids = $this->request->post('ids', 0);
        if (empty($ids) || !preg_match('/^0[\,\d+]+$/', $ids)) {
            return ['status' => 1, 'info' => '非法参数'];
        }
        $type = $this->request->post('type', '');
        if (empty($type)) {
            return ['status' => 3, 'info' => '操作类型不能为空'];
        }
        $field = 'status';
        $value = 1;
        if ($type === 'del' || $type === 'delAll') {
            $field = 'is_delete';
        } elseif ($type === 'xj') {
            $value = 10;
        }
        $res = $g->modifyField($field, $value, ['id' => ['in', $ids]]);
        if (!$res) {
            return ['status' => 4, 'info' => '操作失败'];
        }
        return ['status' => 0, 'info' => '操作成功'];
    }

    /**
     * 修改商品
     * @author 贺强
     * @time   2018-12-10 17:41:53
     * @param  GoodsModel $g GoodsModel 实例
     */
    public function edit(GoodsModel $g)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['name'])) {
                return ['status' => 1, 'info' => '商品名称不能为空'];
            }
            if (empty($param['url'])) {
                return ['status' => 3, 'info' => '商品图片不能为空'];
            }
            if (empty($param['price'])) {
                return ['status' => 5, 'info' => '商品价格不能为空'];
            }
            if (empty($param['min_knife_num'])) {
                return ['status' => 7, 'info' => '所需最低刀数不能为空'];
            }
            if (empty($param['max_knife_num'])) {
                return ['status' => 9, 'info' => '所需最多刀数不能为空'];
            }
            $param['updatetime'] = time();
            // 修改商品
            $res = $g->modify($param, ['id' => $param['id']]);
            if (!$res) {
                return ['status' => 1, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        } else {
            $id = $this->request->get('id');
            if (empty($id) || !preg_match('/^\d+$/', $id)) {
                echo '非法参数';exit;
            }
            $goods = $g->getModel(['id' => $id]);
            if (!empty($goods['url'])) {
                $goods['url1'] = config('WEBSITE') . $goods['url'];
            }
            $time = time();
            return $this->fetch('edit', ['goods' => $goods, 'time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time)]);
        }
    }

    /**
     * 修改商品排序
     * @author 贺强
     * @time   2018-12-10 17:53:55
     * @param  GoodsModel $g GoodsModel 实例
     */
    public function editsort(GoodsModel $g)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $res = $g->modifyField('sort', $param['sort'], ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 2, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        }
    }

    /**
     * 商品列表
     * @author 贺强
     * @time   2018-12-10 16:56:13
     * @param  GoodsModel $g GoodsModel 实例
     */
    public function lists(GoodsModel $g)
    {
        $where = ['is_delete' => 0];
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $g->getList($where, true, "$page,$pagesize", 'sort');
        foreach ($list as &$item) {
            if (!empty($item['url']) && strpos($item['url'], 'https://') === false) {
                $item['url'] = config('WEBSITE') . $item['url'];
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
