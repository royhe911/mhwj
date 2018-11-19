<?php
/**
 * 创建者：   伏伟
 * 创建时间：   2018-05-31
 * 定时任务 任务处理中心
 */

namespace crontab;

class Scheduler
{
    /**
     * @var int
     */
    protected $maxTaskId = 0;

    /**
     * @var array
     */
    protected $taskMap = []; // taskId => task

    /**
     * @var SplQueue
     */
    protected $taskQueue;

    /**
     * Scheduler constructor.
     */
    public function __construct() {
        $this->taskQueue = new \SplQueue();
    }

    /**
     * @param Generator $coroutine
     * @return int
     */
    public function newTask(\Generator $coroutine) {
        $tid = ++$this->maxTaskId;
        $task = new Task($tid, $coroutine);
        $this->taskMap[$tid] = $task;
        $this->schedule($task);
        return $tid;
    }

    /**
     * @param Task $task
     */
    public function schedule(Task $task) {
        $this->taskQueue->enqueue($task);
    }

    /**
     * @return mixed
     */
    public function run() {
        while (!$this->taskQueue->isEmpty()) {
            $task = $this->taskQueue->dequeue();
            $task->run();

            if ($task->isFinished()) {
                unset($this->taskMap[$task->getTaskId()]);
            } else {
                $this->schedule($task);
            }
        }
    }
}