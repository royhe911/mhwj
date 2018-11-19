<?php
/**
 * 创建者：   伏伟
 * 创建时间：   2018-05-31
 * 定时任务 创建任务
 */
namespace crontab;

class Task
{
    /**
     * @var
     */
    protected $taskId;

    /**
     * @var Generator
     */
    protected $coroutine;

    /**
     * @var null
     */
    protected $sendValue = null;

    /**
     * @var bool
     */
    protected $beforeFirstYield = true;

    /**
     * Task constructor.
     * @param $taskId
     * @param Generator $coroutine
     */
    public function __construct($taskId, \Generator $coroutine) {
        $this->taskId = $taskId;
        $this->coroutine = $coroutine;
    }

    /**
     * @return mixed
     */
    public function getTaskId() {
        return $this->taskId;
    }

    /**
     * @param $sendValue
     */
    public function setSendValue($sendValue) {
        $this->sendValue = $sendValue;
    }

    /**
     * @return mixed
     */
    public function run() {
        if ($this->beforeFirstYield) {
            $this->beforeFirstYield = false;
            return $this->coroutine->current();
        } else {
            $retval = $this->coroutine->send($this->sendValue);
            $this->sendValue = null;
            return $retval;
        }
    }

    /**
     * @return bool
     */
    public function isFinished() {
        return !$this->coroutine->valid();
    }
}