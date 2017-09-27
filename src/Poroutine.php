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


    public function __construct($co)
    {
        $this->coroutine = $this->stack($co);
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
//        echo '*******';
//        var_dump($value);
//        echo '*******';
        $this->coroutine->send($value);
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
        $i = 1;
        for(;;) {
            try {
//                echo '||---->' . $i++ . PHP_EOL;
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

                    $gen = $coStack->pop();
                    $gen->send($value);
                    continue;
                }

                yield $gen->send($value);
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