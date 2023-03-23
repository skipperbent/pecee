<?php

namespace Pecee\Queue;

use Pheanstalk\Job;

abstract class Queue
{

    protected string $queue;

    public function __construct()
    {
        $queue = explode('\\', static::class);
        $this->queue = end($queue);
    }

    abstract public function process(Job $job, array $data): void;

    abstract public function send(array $data = []): Job;

    public function getQueue(): string
    {
        return $this->queue;
    }

    public function setQueue(string $queue): void
    {
        $this->queue = $queue;
    }

}