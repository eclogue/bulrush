<?php
/**
 * @license MIT
 * @copyright Copyright (c) 2017
 * @author: bugbear
 * @date: 2017/8/27
 * @time: 下午2:43
 */
namespace Bulrush;

class SystemCall {
    protected $callback;


    public function __construct(callable $callback) {
        $this->callback = $callback;
    }

    public function __invoke(Poroutine $task, Scheduler $scheduler) {
        $callback = $this->callback;
        return $callback($task, $scheduler);
    }

}