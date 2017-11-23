<?php
/**
 * @license MIT
 * @copyright Copyright (c) 2017
 * @author: bugbear
 * @date: 2017/9/10
 * @time: 下午1:20
 */

namespace Bulrush;

use Generator;
use SplStack;
use RuntimeException;

class Poroutine
{

    private $coroutine;

    private $exception = null;

    private $value = null;

    private $return = false;

    public $taskId = '';


    public function __construct(Generator $co, bool $return = false)
    {
        $this->coroutine = $this->stack($co);
        $this->return = $return;
        $this->taskId = uniqid();
    }


    public function send($value)
    {
        $this->value = $value;
    }

    public function getValue() {
        return $this->value;
    }

    public function run()
    {
        if ($this->exception) {
            $res = $this->coroutine->throw($this->exception);
            $this->exception = null;
            return $res;
        }
        $value = $this->coroutine->current();
        $this->coroutine->send($value);
        $this->send($value);

        return $value;
    }

    public function isFinish()
    {
        return !$this->coroutine->valid();
    }

    public function reject($message)
    {
        $this->exception = $message;
    }

    public function stack(Generator $gen)
    {
        $coStack = new SplStack;
        $value = null;
        while(true) {
            try {
                if ($this->exception) {
                    $gen->throw($this->exception);
                    $this->exception = null;
                    continue;
                }
                $value = $gen->current();
                if ($value instanceof Generator) {
                    $coStack->push($gen); // 保存当前的 generator
                    $gen = $value;
                    continue;
                }

                if (!$gen->valid()) {
                    if ($this->return) {
                        $value = $gen->getReturn();
                    }
                    if ($coStack->isEmpty()) {
//                        yield $value;
                        yield $value;
                        break;
                    }

                    $gen = $coStack->pop();
                    $gen->send($value);
                    yield $value;
                    continue;
                }

                $gen->send($value);
                yield $value;

            } catch (RuntimeException $e) {
                if ($coStack->isEmpty()) {
                    throw $e;
                }

                $gen = $coStack->pop();
                $this->exception = $e;
            }
        }

        return $value;
    }

    public function resolve()
    {
        while (!$this->isFinish()) {
            $this->run();
//            var_dump($this->isFinish());
        }

        return $this->value;
    }
}