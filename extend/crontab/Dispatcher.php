<?php
/**
 * 创建者： 伏伟
 * 创建时间： 2018-06-02
 * 任务核心处理类
 */
namespace crontab;

class Dispatcher
{
    /**
     * @var string
     */
    public $namespace = '\\app\\common\\crontab\\';

    /**
     * @var Scheduler
     */
    public $Scheduler;

    /**
     * @var
     */
    public $cacheDir;

    /**
     * @var
     */
    public $crontabDir;

    /**
     * @var array
     */
    private $taskList = [];

    /**
     * Dispatcher constructor.
     */
    public function __construct($cacheDir, $crontabDir)
    {
        $this->cacheDir   = $cacheDir;
        $this->crontabDir = $crontabDir;
        $this->Scheduler  = new Scheduler();
    }

    /**
     * 获取缓存目录
     */
    public function getCacheDir()
    {
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
        return rtrim($this->cacheDir, '/') . '/';
    }

    /**
     * @return string
     */
    public function getcrontabDir()
    {
        return rtrim($this->crontabDir, '/') . '/';
    }

    /**
     * 获取crontab目录下所有的任务文件
     */
    public function getcrontabLists()
    {
        $crontabList    = array();
        $handle         = opendir($this->crontabDir);
        if(!empty($handle)) {
            while($dir = readdir($handle)) {
                if($dir != '.' && $dir != '..' && stripos($dir, '.php')) {
                    $dir            = str_replace('.php', '', $dir);
                    $crontabList[]  = $dir;
                }
            }
        }
        closedir($handle);
        return $crontabList;
    }

    /**
     * 将需要处理的任务加入任务系统
     */
    public function addTask(array $taskList = [])
    {
        if (!empty($taskList)) {
            $this->taskList = $taskList;
        } else {
            $this->taskList = $this->getcrontabLists();
        }
        return $this;
    }

    /**
     * 执行任务
     */
    public function boot()
    {

        for($i = 0; $i < count($this->taskList); $i++) {
            $namespace  = $this->namespace;
            $task       = $this->taskList[$i];
            $cacheDir   = $this->getCacheDir();

            $taskObject = function() use ($namespace, $task, $cacheDir) {
                $crontab = $namespace . ucfirst($task);
                $dispatcher = new DispatcherTask(new $crontab, $cacheDir);
                $dispatcher->start();
                yield;
            };

            $this->Scheduler->newTask($taskObject());
            unset($task, $namespace, $taskObject, $cacheDir);
        }

        $this->Scheduler->run();
    }
}