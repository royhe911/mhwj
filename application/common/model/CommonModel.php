<?php
namespace app\common\model;

use think\Db;
use think\Model;

class CommonModel extends Model
{
    public $table = '';

    /**
     * 添加一条数据
     * @param array $data 要添加的数据 形如：['name'=>'think', 'sex'=>'男', ……]
     * @return int 返回添加成功后的 ID
     */
    public function add($data)
    {
        $id = Db::table($this->table)
        // ->fetchSql(true)
            ->insertGetId($data);
        // $id = db($this->table)->insertGetId($data);
        return $id;
    }

    /**
     * 添加多条数据
     * @param array $data 要添加的数据 形如：[['name'=>'think', 'sex'=>'男', ……], ['name'=>'think', 'sex'=>'男', ……], ……]
     * @return int 返回添加成功的条数
     */
    public function addArr($data)
    {
        $num = Db::table($this->table)
        // ->fetchSql(true)
            ->insertAll($data);
        // $num = db($this->table)->insertAll($data);
        return $num;
    }

    /**
     * 根据主键 ID 删除数据
     * @param array $id 要删除的数据的 ID 形如：[1, 2, 3, ……]
     * @return int 返回影响数据的条数，没有删除返回 0
     */
    public function delById($id)
    {
        $num = Db::table($this->table)
        // ->fetchSql(true)
            ->delete($id);
        // $num = db($this->table)->delete($id);
        return $num;
    }

    /**
     * 根据条件删除数据
     * @param array|string $where 要删除数据的条件 形如：['name'=>'think', 'id'=>['>', 3], ……]或者 SQL 原生字符串，默认为空
     * @return int 返回影响数据的条数，没有删除返回 0
     */
    public function delByWhere($where = null)
    {
        $num = Db::table($this->table)
            ->where($where)
        // ->fetchSql(true)
            ->delete();
        // $num = db($this->table)->where($where)->delete();
        return $num;
    }

    /**
     * 根据条件修改数据
     * @param array $data 要修改的数据 形如：['login_time'  => ['exp','now()'], 'name' => 'thinkphp', ……]
     * @param array|string $where 修改条件 形如：['name'=>'think', 'id'=>['>', 3], ……]或者 SQL 原生字符串，默认更新所有
     * @return int 返回影响数据的条数，没修改任何数据返回 0
     */
    public function modify($data, $where = '1=1')
    {
        $num = Db::table($this->table)
            ->where($where)
        // ->fetchSql(true)
            ->update($data);
        // $num = db($this->table)->update($data);
        return $num;
    }

    /**
     * 根据条件更新某个字段的值
     * @param string $field 要更新的字段
     * @param string $value 要更新的字段的值
     * @param array|string $where 更新条件 形如：['name'=>'think', 'id'=>['>', 3], ……]或者 SQL 原生字符串，默认更新所有
     * @return int 返回影响数据的条数，没修改任何数据字段返回 0
     */
    public function modifyField($field, $value, $where = '1=1')
    {
        $num = Db::table($this->table)
            ->where($where)
            // ->fetchSql(true)
            ->setField($field, $value);
        // $num = db($this->table)->where($where)->setField($field, $value);
        return $num;
    }

    /**
     * 根据条件自增一个字段的值，可以延迟更新
     * @param string $field 要自增的字段
     * @param array|string $where 自增条件，形如：['name'=>'think', 'id'=>['>', 3], ……]或者 SQL 原生字符串，默认更新所有
     * @param int $value 要自增的值，默认为 1
     * @param int $delay 要延迟更新的时候，单位妙，默认 0 不延迟
     * @return int 返回影响数据的条数
     */
    public function increment($field, $where = '1=1', $value = 1, $delay = 0)
    {
        $num = Db::table($this->table)
            ->where($where)
        // ->fetchSql(true)
            ->setInc($field, $value, $delay);
        // $num = db($this->table)->where($where)->setInc($field, $value, $delay);
        return $num;
    }

    /**
     * 根据条件自减一个字段的值，可以延迟更新
     * @param string $field 要自减的字段
     * @param array $where 自减条件，形如：['name'=>'think', 'id'=>['>', 3], ……]或者 SQL 原生字符串，默认更新所有
     * @param int $value 要自减的值，默认为 1
     * @param int $delay 要延迟更新的时候，单位妙，默认 0 不延迟
     * @return int 返回影响数据的条数
     */
    public function decrement($field, $where = '1=1', $value = 1, $delay = 0)
    {
        $num = Db::table($this->table)
            ->where($where)
        // ->fetchSql(true)
            ->setDec($field, $value, $delay);
        // $num = db($this->table)->where($where)->setDec($field, $value, $delay);
        return $num;
    }

    /**
     * 根据条件获取总数量
     * @param array|string $where 查询条件，形如：['name'=>'think', 'id'=>['>', 3], ……]或者 SQL 原生字符串，默认为空
     * @param string $group 对结果集进行分组，只能使用一个字符串，即字段名
     * @return int 返回总数量
     */
    public function getCount($where = null, $group = null)
    {
        $count = Db::table($this->table)->group($group)->where($where)->count();
        return $count;
    }

    /**
     * 根据条件查询一条数据
     * @param array|string $where 查询条件，形如：['name'=>'think', 'id'=>['>', 3], ……]或者 SQL 原生字符串，默认为空
     * @param array|string $field 要查询的字段，形如：['id', 'name', ……]或者'id, name, ……'，默认显式的获取数据表的所有字段列表
     * @param array|string $order 按某(些)字段排序，形如：['order','id'=>'desc']或者'order, id desc'，默认按数据库中原顺序
     * @param array|string $whereOr 查询 or 条件，形如：['name'=>'think', 'id'=>['>', 3], ……]或者 SQL 原生字符串，默认为空
     * @return array 返回查询结果(数组)，若不存在返回 null
     */
    public function getModel($where = null, $field = true, $order = '', $whereOr = null)
    {
        $model = Db::table($this->table)
            ->field($field)
            ->order($order)
            ->where($where)
            ->whereOr($whereOr)
        // ->fetchSql(true)
            ->find();
        // $model = db($this->table)->where($where)->find();
        return $model;
    }

    /**
     * 根据条件查询数据集
     * @param array|string $where 查询条件，形如：['name'=>'think', 'id'=>['>', 3], ……]或者 SQL 原生字符串，默认为空
     * @param array|string $field 要查询的字段，形如：['id', 'name', ……]或者'id, name, ……'，默认显式的获取数据表的所有字段列表
     * @param string $page 分页查询，形如：'1, 10'，其中字符串中第一个数字表示第几页，第二个数字表示每页多少条
     * @param array|string $order 按某(些)字段排序，形如：['order','id'=>'desc']或者'order, id desc'，默认按数据库中原顺序
     * @param string $group 对结果集进行分组，只能使用一个字符串，即字段名
     * @param string $having 用于配合group方法完成从分组的结果中筛选(通常是聚合条件)数据，比如：'count(name)>0'
     * @return array 返回查询结果(数组)，若不存在返回 null
     */
    public function getList($where = null, $field = true, $page = null, $order = 'id', $group = null, $having = null)
    {
        $list = Db::table($this->table)
            ->field($field)
            ->order($order)
            ->page($page)
            ->group($group)
            ->having($having)
            ->where($where)
        // ->fetchSql(true)
            ->select();
        return $list;
    }

    /**
     * 根据条件查询数据集
     * @param array|string $where 查询条件，形如：['name'=>'think', 'id'=>['>', 3], ……]或者 SQL 原生字符串，默认为空
     * @param array|string $field 要查询的字段，形如：['id', 'name', ……]或者'id, name, ……'，默认显式的获取数据表的所有字段列表
     * @param string $limit 分页查询，形如：'1, 10'，其中字符串中第一个数字表示从第几条开始，第二个数字表示查询多少条
     * @param array|string $order 按某(些)字段排序，形如：['order','id'=>'desc']或者'order, id desc'，默认按数据库中原顺序
     * @param string $group 对结果集进行分组，只能使用一个字符串，即字段名
     * @param string $having 用于配合group方法完成从分组的结果中筛选(通常是聚合条件)数据，比如：'count(name)>0'
     * @return array 返回查询结果(数组)，若不存在返回 null
     */
    public function getLimitList($where, $field = true, $limit = null, $order = 'id', $group = null, $having = null)
    {
        $list = Db::table($this->table)
            ->field($field)
            ->order($order)
            ->limit($limit)
            ->group($group)
            ->having($having)
            ->where($where)
        // ->fetchSql(true)
            ->select();
        return $list;
    }

    /**
     * 联系查询总数
     * @param array $join 联表查询，形如：[['think_card c','a.card_id=c.id'], ……]
     * @where array|string $where 查询条件，形如：['name'=>'think', 'id'=>['>', 3], ……]或者 SQL 原生字符串，默认为空
     * @param string $alias 用于设置当前数据表的别名，默认别名为 a
     * @return int 返回总数量
     */
    public function getJoinCount($join, $where = null, $alias = 'a')
    {
        $count = Db::table($this->table)
            ->alias($alias)
            ->join($join)
            ->where($where)
            ->count();
        return $count;
    }

    /**
     * 联表查询
     * @param array $join 联表查询，形如：[['think_card c','a.card_id=c.id'], ……]
     * @param array|string $where 查询条件，形如：['name'=>'think', 'id'=>['>', 3], ……]或者 SQL 原生字符串，默认为空
     * @param string $page 分页查询，形如：'1, 10'，其中字符串中第一个数字表示第几页，第二个数字表示每页多少条
     * @param array|string $field 要查询的字段，形如：['id', 'name', ……]或者'id, name, ……'，默认显式的获取数据表的所有字段列表
     * @param string $alias 用于设置当前数据表的别名，默认别名为 a
     * @return array 返回查询结果(数组)，若不存在返回 null
     */
    public function getJoinList($join, $where = null, $page = null, $field = true, $alias = 'a')
    {
        $list = Db::table($this->table)
            ->alias($alias)
            ->field($field)
            ->join($join)
            ->where($where)
            ->page($page)
        // ->fetchSql(true)
            ->select();
        return $list;
    }

    /**
     * UNION 查询
     * @param array $union 联合的 SQL 语句，形如：['SELECT name FROM table1','SELECT name FROM table2']，每个union方法相当于一个独立的SELECT语句
     * @param array|string $where 查询条件，形如：['name'=>'think', 'id'=>['>', 3], ……]或者 SQL 原生字符串，默认为空
     * @param array|string $field 要查询的字段，形如：['id', 'name', ……]或者'id, name, ……'，默认显式的获取数据表的所有字段列表
     * @param boolean $is_all 是否是 UNION ALL 操作
     * @param string $alias 用于设置当前数据表的别名，默认别名为 a
     * @return array 返回查询结果(数组)，若不存在返回 null
     */
    public function getUnionList($union, $where = null, $field = true, $is_all = false, $alias = 'a')
    {
        $list = Db::table($this->table)
            ->alias($alias)
            ->field($field)
            ->where($where)
            ->union($union, $is_all)
        // ->fetchSql(true)
            ->select();
        return $list;
    }
}
