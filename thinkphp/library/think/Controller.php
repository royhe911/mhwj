<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think;

\think\Loader::import('controller/Jump', TRAIT_PATH, EXT);

use app\common\model\AdminModel;
use app\common\model\MenuModel;
use app\common\model\RoleModel;
use think\Exception;
use think\exception\ValidateException;

class Controller
{
    use \traits\controller\Jump;

    // 视图类实例
    protected $view;
    // Request实例
    protected $request;
    // 验证失败是否抛出异常
    protected $failException = false;
    // 是否批量验证
    protected $batchValidate = false;

    /**
     * 前置操作方法列表
     * @var array $beforeActionList
     * @access protected
     */
    protected $beforeActionList = [];

    /**
     * 架构函数
     * @param Request $request Request对象
     * @access public
     */
    public function __construct(Request $request = null)
    {
        if (is_null($request)) {
            $request = Request::instance();
        }
        $this->view    = View::instance(Config::get('template'), Config::get('view_replace_str'));
        $this->request = $request;

        // 控制器初始化
        $this->_initialize();

        // 前置操作方法
        if ($this->beforeActionList) {
            foreach ($this->beforeActionList as $method => $options) {
                is_numeric($method) ?
                $this->beforeAction($options) :
                $this->beforeAction($method, $options);
            }
        }
        $admin = Session::get('admin');
        if (!empty($admin)) {
            $a    = new AdminModel();
            $user = $a->getModel(['id' => $admin['id']]);
            if ($user['status'] !== 8) {
                session('admin', null);
                $this->error('该账号被禁用，请更换', url('/admin/login'));
            }
            if ($admin['pwd'] !== $user['pwd']) {
                session('admin', null);
                $this->error('登录超时，请重新登录', url('/admin/login'));
            }
        }
    }

    // 初始化
    protected function _initialize()
    {
    }

    /**
     * 判断用户是否登录
     */
    protected function is_login()
    {
        $admin  = Session::get('admin');
        $action = $this->request->get('action');
        if (empty($admin) && empty($action)) {
            $this->error('登录超时，请重新登录', url('/admin/login'));
        }
        return $admin;
    }

    /**
     * 判断登录用户是否有权限
     * @Author 贺强
     * @date   2018-08-31
     * @param  string     $identity 操作/访问的方法
     */
    public function is_valid($identity = '')
    {
        $admin = $this->is_login();
        if ($admin['role_id'] === 1) {
            return $admin;
        }
        if (empty($identity)) {
            $this->error('您无权访问或操作1');
        }
        if (strrpos($identity, '\\') !== false) {
            $identity = substr($identity, strrpos($identity, '\\') + 1);
        }
        $m    = new MenuModel();
        $menu = $m->getModel(['identity' => $identity], 'id');
        if (empty($menu)) {
            $this->error('您无权访问或操作2');
        }
        if (!in_array($menu['id'], $admin['roles'])) {
            $this->error('您无权访问或操作3');
        }
        return $admin;
    }

    /**
     * 根据条件获取用户
     * @Author 贺强
     * @date   2018-08-31
     * @param  array      $where 条件
     * @param  string     $field 要获取的字段
     */
    protected function getUsers($where = [], $field = 'id,nickname')
    {
        $where['is_delete'] = 0;
        $a                  = new AdminModel();
        $list               = $a->getList($where, $field);
        return $list;
    }

    /**
     * 获取用户角色
     */
    protected function getRoles($where = null)
    {
        $where['is_delete'] = 0;
        $r                  = new RoleModel();
        $list               = $r->getList($where, 'id,`name`');
        return $list;
    }

    /**
     * 前置操作
     * @access protected
     * @param string $method  前置操作方法名
     * @param array  $options 调用参数 ['only'=>[...]] 或者['except'=>[...]]
     */
    protected function beforeAction($method, $options = [])
    {
        if (isset($options['only'])) {
            if (is_string($options['only'])) {
                $options['only'] = explode(',', $options['only']);
            }
            if (!in_array($this->request->action(), $options['only'])) {
                return;
            }
        } elseif (isset($options['except'])) {
            if (is_string($options['except'])) {
                $options['except'] = explode(',', $options['except']);
            }
            if (in_array($this->request->action(), $options['except'])) {
                return;
            }
        }

        call_user_func([$this, $method]);
    }

    /**
     * 加载模板输出
     * @access protected
     * @param string $template 模板文件名
     * @param array  $vars     模板输出变量
     * @param array  $replace  模板替换
     * @param array  $config   模板参数
     * @return mixed
     */
    protected function fetch($template = '', $vars = [], $replace = [], $config = [])
    {
        return $this->view->fetch($template, $vars, $replace, $config);
    }

    /**
     * 渲染内容输出
     * @access protected
     * @param string $content 模板内容
     * @param array  $vars    模板输出变量
     * @param array  $replace 替换内容
     * @param array  $config  模板参数
     * @return mixed
     */
    protected function display($content = '', $vars = [], $replace = [], $config = [])
    {
        return $this->view->display($content, $vars, $replace, $config);
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param mixed $name  要显示的模板变量
     * @param mixed $value 变量的值
     * @return void
     */
    protected function assign($name, $value = '')
    {
        $this->view->assign($name, $value);
    }

    /**
     * 初始化模板引擎
     * @access protected
     * @param array|string $engine 引擎参数
     * @return void
     */
    protected function engine($engine)
    {
        $this->view->engine($engine);
    }

    /**
     * 设置验证失败后是否抛出异常
     * @access protected
     * @param bool $fail 是否抛出异常
     * @return $this
     */
    protected function validateFailException($fail = true)
    {
        $this->failException = $fail;
        return $this;
    }

    /**
     * 验证数据
     * @access protected
     * @param array        $data     数据
     * @param string|array $validate 验证器名或者验证规则数组
     * @param array        $message  提示信息
     * @param bool         $batch    是否批量验证
     * @param mixed        $callback 回调方法（闭包）
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate($data, $validate, $message = [], $batch = false, $callback = null)
    {
        if (is_array($validate)) {
            $v = Loader::validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                list($validate, $scene) = explode('.', $validate);
            }
            $v = Loader::validate($validate);
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }
        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        if (is_array($message)) {
            $v->message($message);
        }

        if ($callback && is_callable($callback)) {
            call_user_func_array($callback, [$v, &$data]);
        }

        if (!$v->check($data)) {
            if ($this->failException) {
                throw new ValidateException($v->getError());
            } else {
                return $v->getError();
            }
        } else {
            return true;
        }
    }
}
