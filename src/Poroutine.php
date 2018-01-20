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
        $this->stack = new SplStack();
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
        $value = null;
        while(true) {
            try {
                if ($this->exception) {
                    $gen->throw($this->exception);
                    $this->exception = null;
                    continue;
                }

                if (!$gen->valid()) {
                    if ($this->return) {
                        $value = $gen->getReturn();
                        if ($value instanceof Generator && $value->valid()) {
                            $this->stack->push($value);
                            $gen = $value;
                            continue;
                        }
                    }

                    if ($this->stack->isEmpty()) {
                        yield $value;
                        break;
                    }

                    $gen = $this->stack->pop();
                    yield $gen->send($value);
                    continue;
                }

                $value = $gen->current();
                if ($value instanceof Generator) {
                    $this->stack->push($gen); // 保存当前的 generator
                    $gen = $value;
                    continue;
                }

                $gen->send($value);
                yield $value;
            } catch (RuntimeException $err) {
                if ($this->stack->isEmpty()) {
                    $this->exception = $err;
                    $gen->throw($err);
                } else {
                    $gen = $this->stack->pop();
                    $this->exception = $err;
                }
            }
        }

        return $value;
    }

    public static function resolve(Generator $gen)
    {
        $co = new static($gen, true);
        while (!$co->isFinish()) {
            $co->run();
        }

        return $co->value;
    }

}
