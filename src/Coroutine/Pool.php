<?php

namespace Dux\Coroutine;

use Closure;
use Co\Channel;
use Exception;
use Swoole\Coroutine;

class Pool
{
    private Channel $channel;

    public function __construct(public Closure $callback, public int $minSize = 30, public int $maxSize = 300, public int $timeOut = 10)
    {
        $this->channel = new Channel($this->maxSize);
    }

    public function start(): void
    {
        for ($i = 0; $i < $this->minSize; $i++) {
            $this->add();
        }
    }

    public function add(): mixed
    {
        $conn = call_user_func($this->callback);
        $this->channel->push($conn);
        return $conn;
    }

    public function get(): mixed
    {
        dump($this->channel->isEmpty());
        if ($this->channel->isEmpty()) {
            dump('add ' . $this->channel->length());
            return $this->add();
        }
        dump('get ' . Coroutine::getCid());
        $conn = $this->channel->pop($this->timeOut * 1000);
        if (!$conn) {
            throw new Exception('get data pool timeout');
        }
        return $conn;
    }

    public function release($conn): void
    {
        if ($this->channel->isFull()) {
            return;
        }
        dump('release ' . Coroutine::getCid() . ' num ' . $this->channel->length());
        $this->channel->push($conn);
    }


}