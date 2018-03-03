<?php
namespace Pecee\Queue;

abstract class Queue
{
    protected $job;
    protected $queue;

    public function __construct()
    {
        $this->queue = static::class;
    }

    abstract public function process($job, $data);

    abstract public function send(array $data = []);

    public function setJob($job)
    {
        $this->job = $job;
    }

    public function getJob() {
        return $this->job;
    }

    /**
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    public function setQueue($queue)
    {
        $this->queue = $queue;
    }

}