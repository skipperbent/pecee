<?php

namespace Pecee\Queue;

use Pheanstalk\Job;

abstract class Queue
{

    abstract public function process(Job $job, array $data): void;

    abstract public function send(array $data = []): Job;

    public function getQueue(): string
    {
        return str_replace('\\', '.', basename(get_class($this)));
    }

}