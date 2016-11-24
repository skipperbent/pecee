<?php
namespace Pecee\Queue;

abstract class Queue
{

	protected $queue;

	public function __construct()
	{
		$queue = explode('\\', static::class);
		$this->queue = end($queue);
	}

	abstract public function process($job, $data);

	abstract function send(array $data = []);

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