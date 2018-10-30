<?php
namespace app\admin\controller;

use app\common\model\GameModel;

/**
 * Game-控制器
 * @author 贺强
 * @time   2018-10-29 11:06:19
 */
class Game extends \think\Controller
{
    /**
     * 添加游戏
     * @author 贺强
     * @time   2018-10-29 11:26:58
     * @param  GameModel $g GameModel 实例
     */
    public function add(GameModel $g)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['name'])) {
                return ['status' => 1, 'info' => '游戏名称不能为空'];
            }
            if (empty($param['url'])) {
                return ['status' => 2, 'info' => '游戏图片不能为空'];
            }
            $param['addtime'] = time();
            $res              = $g->add($param);
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
     * 删除游戏
     * @author 贺强
     * @time   2018-10-29 11:38:01
     * @param  GameModel $g GameModel 实例
     */
    public function del(GameModel $g)
    {
        $ids = $this->request->post('ids', 0);
        if (empty($ids) || preg_match('/^0[\,\d+]+$/', $ids)) {
            return ['status' => 1, 'info' => '非法参数'];
        }
        $res = $g->delByWhere(['id' => ['in', $ids]]);
        if (!$res) {
            return ['status' => 4, 'info' => '删除失败'];
        }
        return ['status' => 0, 'info' => '删除成功'];
    }

    /**
     * 修改游戏
     * @author 贺强
     * @time   2018-10-29 11:41:55
     * @param  GameModel $g GameModel 实例
     */
    public function edit(GameModel $g)
    {
        if ($this->request->isAjax()) {
            $param = $this->request->post();
            if (empty($param['id'])) {
                return ['status' => 1, 'info' => '非法参数'];
            }
            if (empty($param['name'])) {
                return ['status' => 2, 'info' => '游戏名称不能为空'];
            }
            if (empty($param['url'])) {
                return ['status' => 3, 'info' => '游戏图片不能为空'];
            }
            $res = $g->modify($param, ['id' => $param['id']]);
            if ($res === false) {
                return ['status' => 2, 'info' => '修改失败'];
            }
            return ['status' => 0, 'info' => '修改成功'];
        } else {
            $id = $this->request->get('id');
            if (empty($id) || !preg_match('/^\d+$/', $id)) {
                echo '非法参数';exit;
            }
            $game = $g->getModel(['id' => $id]);
            if (!empty($game['url'])) {
                $game['url1'] = config('WEBSITE') . $game['url'];
            }
            $time = time();
            return $this->fetch('edit', ['game' => $game, 'time' => $time, 'token' => md5(config('UPLOAD_SALT') . $time)]);
        }
    }

    /**
     * 游戏列表
     * @author 贺强
     * @time   2018-10-29 11:08:03
     * @param  GameModel $g GameModel 实例
     * @return array        返回游戏数据集
     */
    public function lists(GameModel $g)
    {
        $where = [];
        // 分页参数
        $page     = intval($this->request->get('page', 1));
        $pagesize = intval($this->request->get('pagesize', config('PAGESIZE')));
        $list     = $g->getList($where, true, "$page,$pagesize");
        foreach ($list as &$item) {
            if (!empty($item['url'])) {
                //config('WEBSITE') .
                $item['url'] = $item['url'];
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
