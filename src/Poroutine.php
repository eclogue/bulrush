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


    public function __construct(Generator $co, bool $return)
    {
        $this->coroutine = $this->stack($co);
        $this->return = $return;
    }


    public function send($value)
    {
        $this->value = $value;
    }

    public function run()
    {
        if ($this->exception) {
            $res = $this->coroutine->throw($this->exception);
            $this->exception = null;
            return $res;
        }
        $value = $this->coroutine->current();
        $this->coroutine->next();

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
        for(;;) {
            try {
                if ($this->exception) {
                    $gen->throw($this->exception);
                    $this->exception = null;
                    continue;
                }

                if ($gen->valid()) {
                    $value = $gen->current();
                }
                if ($value instanceof Generator) {
                    $coStack->push($gen); // 保存当前的 generator
                    $gen = $value;
                    continue;
                }

                if (!$gen->valid()) {
                    if ($coStack->isEmpty()) {
                        return $value;
                    }

                    if ($this->return) {
                        $value = $gen->getReturn();
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
}