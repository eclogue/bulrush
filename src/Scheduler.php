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

    public $buffer = [];

    public $take = [];

    public $put = [];

    public $tasks = [];

    public function __construct()
    {
        $this->queue = new \SplQueue();
        $this->tasks = new \SplStack();
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
                $this->buffer[$task->taskId] = $task->getValue();
                continue;
            }

            $this->schedule($task);
        }

        return true;
    }

    public function take()
    {
        if ($this->count()) {
            throw new \Exception('Scheduler is not finish');
        }
        if ($this->tasks->isEmpty()) {
            return null;
        }
        $taskId = $this->tasks->pop();


        return $this->buffer[$taskId] ?? null;

    }


    public function add(Generator $co, bool $return = false)
    {
        $task = new Poroutine($co, $return);
        $this->tasks->push($task->taskId);
        $this->schedule($task);
    }


    public function schedule(Poroutine $task)
    {
        $this->queue->enqueue($task);
    }

    public function count()
    {
        return $this->queue->count();
    }
}