<?php
/**
 * @license MIT
 * @copyright Copyright (c) 2017
 * @author: bugbear
 * @date: 2017/9/10
 * @time: 下午2:05
 */

namespace Bulrush;

use Generator;

class Scheduler
{

    private $queue;

    public function __construct()
    {
        $this->queue = new \SplQueue();
    }

    public function run()
    {
        while (!$this->queue->isEmpty()) {
            $task = $this->queue->dequeue();
            $result = $task->run();
            try {
                if ($result instanceof SystemCall) {
                    $result($task, $this);
                }
            } catch (\RuntimeException $err) {
                $task->reject($err);
                $this->schedule($task);
                continue;
            }
            if ($task->isFinish()) {
                continue;
            }
            $this->schedule($task);
        }
    }


    public function add(Generator $co) {
        $task = new Poroutine($co);
        $this->schedule($task);
    }

    public function test()
    {
        $this->queue->enqueue(1);
    }


    public function schedule(Poroutine $task)
    {
        $this->queue->enqueue($task);
    }
}