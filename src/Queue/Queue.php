<?php

namespace Pecee\Queue;

use Pheanstalk\Job;

abstract class Queue
{

    protected string $queue = self::class;

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