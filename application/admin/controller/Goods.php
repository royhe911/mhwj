<?php
namespace app\admin\controller;

use app\common\model\GoodsDistributeModel;
use app\common\model\GoodsModel;
use app\common\model\GoodsSkinModel;
use app\common\model\GoodsTaskModel;
use app\common\model\UserModel;

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
     * 修改幸运儿
     * @author 贺强
     * @time   2018-12-12 10:12:34
     * @param  GoodsModel $g GoodsModel 实例
     */
    public function editlucky(GoodsModel $g)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id']) || empty($param['lucky'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $lucky = $param['lucky'];
            $lucky = explode(',', $lucky);
            foreach ($lucky as &$ly) {
                $ly -= 1;
            }
            $lucky = implode(',', $lucky);
            $res   = $g->modifyField('lucky', $lucky, ['id' => $param['id']]);
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
            if (!empty($item['lucky'])) {
                $lucky = explode(',', $item['lucky']);
                foreach ($lucky as &$ly) {
                    $ly += 1;
                }
                $item['lucky'] = implode(',', $lucky);
            }
        }
        $count = $g->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('list', ['list' => $list, 'pages' => $pages]);
    }

    /**
     * 砍价成功列表
     * @author 贺强
     * @time   2018-12-13 14:19:55
     * @param  GoodsTaskModel $gt GoodsTaskModel 实例
     */
    public function prizes(GoodsTaskModel $gt)
    {
        $where = ['status' => ['in', [8, 10]]];
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $gt->getList($where, true, "$page,$pagesize", ['addtime' => 'desc', 'status']);
        $pages    = 0;
        if ($list) {
            $uids  = array_column($list, 'uid');
            $u     = new UserModel();
            $users = $u->getList(['id' => ['in', $uids]], ['id', 'nickname', 'avatar']);
            $users = array_column($users, null, 'id');
            $gids  = array_column($list, 'goods_id');
            $g     = new GoodsModel();
            $goods = $g->getList(['id' => ['in', $gids]], ['id', 'name']);
            $goods = array_column($goods, 'name', 'id');
            foreach ($list as &$item) {
                if (!empty($users[$item['uid']])) {
                    $user = $users[$item['uid']];
                    // 属性赋值
                    $item['nickname'] = $user['nickname'];
                    $item['avatar']   = $user['avatar'];
                } else {
                    $item['nickname'] = '';
                    $item['avatar']   = '';
                }
                if (!empty($goods[$item['goods_id']])) {
                    $item['goods_name'] = $goods[$item['goods_id']];
                } else {
                    $item['goods_name'] = '';
                }
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
                if ($item['is_lucky']) {
                    $item['is_lucky'] = '是';
                } else {
                    $item['is_lucky'] = '不是';
                }
                if ($item['box1']) {
                    $item['box1'] = '已使用';
                } else {
                    $item['box1'] = '未使用';
                }
                if ($item['box2']) {
                    $item['box2'] = '已使用';
                } else {
                    $item['box2'] = '未使用';
                }
                if ($item['status'] === 8) {
                    $item['status_txt'] = '砍价成功';
                } elseif ($item['status'] === 10) {
                    $item['status_txt'] = '已派发奖品';
                } else {
                    $item['status_txt'] = '';
                }
            }
            $count = $gt->getCount($where);
            $pages = ceil($count / $pagesize);
        }
        return $this->fetch('prizes', ['list' => $list, 'pages' => $pages]);
    }

    /**
     * 发放奖品
     * @author 贺强
     * @time   2018-12-13 17:26:47
     * @param  GoodsDistributeModel $gd GoodsDistributeModel 实例
     */
    public function ffjp(GoodsDistributeModel $gd)
    {
        $gt = new GoodsTaskModel();
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['uid'])) {
                return ['status' => 1, 'info' => '中奖者ID不能为空'];
            }
            if (empty($param['wx'])) {
                return ['status' => 3, 'info' => '中奖者微信不能为空'];
            }
            if (empty($param['goods_name'])) {
                return ['status' => 5, 'info' => '奖品名称不能为空'];
            }
            if (empty($param['mobile'])) {
                return ['status' => 7, 'info' => '中奖者手机不能为空'];
            }
            if (empty($param['skinID'])) {
                return ['status' => 9, 'info' => '皮肤ID不能为空'];
            }
            $task_id = $param['task_id'];
            unset($param['task_id']);
            $res = $gd->add($param);
            if ($res) {
                $gt->modifyField('status', 10, ['id' => $task_id]);
                return ['status' => 0, 'info' => '发放成功'];
            } else {
                return ['status' => 4, 'info' => '发放失败'];
            }
        } else {
            $id    = $this->request->get('id');
            $task  = $gt->getModel(['id' => $id]);
            $g     = new GoodsModel();
            $goods = $g->getModel(['id' => $task['goods_id']]);
            $gs    = new GoodsSkinModel();
            $skin  = $gs->getList(['goods_id' => $task['goods_id']], ['id', 'name', 'url', 'price']);
            $u     = new UserModel();
            $user  = $u->getModel(['id' => $task['uid']]);
            $data  = ['uid' => $task['uid'], 'goods_name' => $goods['name'], 'mobile' => $user['mobile'], 'avatar' => $user['avatar'], 'skin' => $skin, 'task_id' => $id];
            return $this->fetch('ffjp', ['data' => $data]);
        }
    }
}
