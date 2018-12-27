<?php
namespace app\admin\controller;

use app\common\model\PrizeDistributeModel;
use app\common\model\PrizeModel;
use app\common\model\UserModel;

/**
 * 抽奖-控制器
 * @author 贺强
 * @time   2018-12-26 17:59:24
 */
class Prize extends \think\Controller
{
    /**
     * 添加奖品
     * @author 贺强
     * @time   2018-12-26 18:23:07
     * @param  PrizeModel $p PrizeModel 实例
     */
    public function add(PrizeModel $p)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['name']) || empty($param['url']) || empty($param['desc']) || empty($param['type']) || empty($param['count'])) {
                return ['status' => 3, 'info' => '非法参数'];
            }
            $param['code']    = get_millisecond();
            $param['addtime'] = time();
            $res              = $p->add($param);
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
     * 删除奖品
     * @author 贺强
     * @time   2018-12-26 18:42:27
     * @param  PrizeModel $p PrizeModel 实例
     */
    public function del(PrizeModel $p)
    {
        if ($this->request->isAjax()) {
            $ids = $this->request->post('ids');
            $res = $p->delByWhere(['id' => ['in', $ids]]);
            if (!$res) {
                return ['status' => 4, 'info' => '删除失败'];
            }
            return ['status' => 0, 'info' => '删除成功'];
        }
    }

    /**
     * 修改奖品
     * @author 贺强
     * @time   2018-12-26 19:07:03
     * @param  PrizeModel $p PrizeModel 实例
     */
    public function edit(PrizeModel $p)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['name'])) {
                return ['status' => 1, 'info' => '奖品名称不能为空'];
            }
            if (empty($param['url'])) {
                return ['status' => 3, 'info' => '奖品图片不能为空'];
            }
            $param['updatetime'] = time();
            // 修改奖品
            $res = $p->modify($param, ['id' => $param['id']]);
            if (!$res) {
                return ['status' => 4, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        } else {
            $id = $this->request->get('id');
            if (empty($id) || !preg_match('/^\d+$/', $id)) {
                echo '非法参数';exit;
            }
            $prize = $p->getModel(['id' => $id]);
            if (!empty($prize['url'])) {
                $prize['url1'] = config('WEBSITE') . $prize['url'];
            }
            $time = time();
            return $this->fetch('edit', ['prize' => $prize, 'time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time)]);
        }
    }

    /**
     * 修改开奖人数
     * @author 贺强
     * @time   2018-12-12 10:12:34
     * @param  PrizeModel $g PrizeModel 实例
     */
    public function editcount(PrizeModel $g)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id']) || empty($param['count'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            $count = $param['count'];
            $res   = $g->modifyField('count', $count, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 2, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        }
    }

    /**
     * 奖品列表
     * @author 贺强
     * @time   2018-12-26 18:12:23
     * @param  PrizeModel $p PrizeModel 实例
     */
    public function lists(PrizeModel $p)
    {
        $where    = [];
        $page     = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', config('PAGESIZE'));
        $list     = $p->getList($where, true, "$page,$pagesize");
        foreach ($list as &$item) {
            if (!empty($item['url'])) {
                $url = $item['url'];
                if (strpos($url, 'http://') === false && strpos($url, 'https://')) {
                    $item['url'] = config('WEBSITE') . $url;
                }
            }
            if (!empty($item['addtime'])) {
                $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
            }
            switch ($item['status']) {
                case 0:
                    $item['status'] = '未开始';
                    break;
                case 1:
                    $item['status'] = '已开始';
                    break;
                default:
                    $item['status'] = '已结束';
                    break;
            }
        }
        $count = $p->getCount($where);
        $pages = ceil($count / $pagesize);
        return $this->fetch('list', ['list' => $list, 'pages' => $pages]);
    }

    /**
     * 中奖者列表
     * @author 贺强
     * @time   2018-12-26 19:28:14
     * @param  PrizeDistributeModel $pd PrizeDistributeModel 实例
     */
    public function users(PrizeDistributeModel $pd)
    {
        $page     = $this->request->get('page', 1);
        $pagesize = $this->request->get('pagesize', config('PAGESIZE'));
        $list     = $pd->getList([], true, "$page,$pagesize", 'addtime desc');
        if ($list) {
            $uids = array_column($list, 'uid');
            $pids = array_column($list, 'prize_id');
            $u    = new UserModel();
            $ulis = $u->getList(['id' => ['in', $uids]], ['id', 'nickname', 'avatar']);
            $ulis = array_column($ulis, null, 'id');
            $p    = new PrizeModel();
            $plis = $p->getList(['id' => ['in', $pids]], ['name', 'id']);
            $plis = array_column($plis, 'name', 'id');
            foreach ($list as &$item) {
                if (!empty($item['addtime'])) {
                    $item['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                }
                if (!empty($item['grant_time'])) {
                    $item['grant_time'] = date('Y-m-d H:i:s', $item['grant_time']);
                }
                if ($item['is_grant']) {
                    $item['status'] = '已发放';
                } else {
                    $item['status'] = '未发放';
                }
                if (!empty($ulis[$item['uid']])) {
                    $user = $ulis[$item['uid']];
                    // 属性赋值
                    $item['nickname'] = $user['nickname'];
                    $item['avatar']   = $user['avatar'];
                } else {
                    $item['nickname'] = '';
                    $item['avatar']   = '';
                }
                if (!empty($plis[$item['prize_id']])) {
                    $item['name'] = $plis[$item['prize_id']];
                } else {
                    $item['name'] = '';
                }
            }
        }
        $count = $pd->getCount();
        $pages = ceil($count / $pagesize);
        return $this->fetch('users', ['list' => $list, 'pages' => $pages]);
    }
}
