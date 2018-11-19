<?php
/**
 * 创建者：   伏伟
 * 创建时间：   2018-05-31
 * 定时任务 任务设置抽象类
 */

namespace crontab;

abstract class CrontabAbstract implements CrontabInterface
{
    /**
     * @var null
     */
    public $crontabName = NULL;

    /**
     * @var bool
     */
    public $isStop = false;
}