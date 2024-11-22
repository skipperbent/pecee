<?php

namespace Pecee\Queue;

use Pheanstalk\Pheanstalk;
use Pheanstalk\Values\TubeName;

class Worker
{

    /**
     * @var array|Queue[]
     */
    protected array $workers;
    protected array $workerInstances;
    protected Pheanstalk $client;

    protected ?\Closure $error = null;
    protected ?\Closure $jobDelete = null;

    public static function getClient(): Pheanstalk
    {
        return Pheanstalk::create(env('BEANTALKD_HOST', '127.0.0.1'), env('BEANTALKD_PORT', 11300));
    }

    public function onError(?\Closure $function): void
    {
        $this->error = $function;
    }

    public function onJobDelete(?\Closure $function): void
    {
        $this->jobDelete = $function;
    }

    public function __construct(array $workers)
    {
        $this->workers = $workers;
        $this->client = static::getClient();

        $this->onError(function (\Exception $e, string $queueName) {
            echo "[ERROR] $queueName: {$e->getMessage()}\n";
        });

        foreach ($this->workers as $worker) {
            $workerClass = new $worker();
            if ($workerClass instanceof Queue) {
                $this->workerInstances[$workerClass->getQueue()] = $workerClass;
                $this->client->watch(new TubeName($workerClass->getQueue()));
            }
        }
    }

    public function work(): void
    {
        while ($job = $this->client->reserve()) {

            try {
                $queueName = 'worker';
                $data = json_decode($job->getData(), true);

                if (isset($data['queue']) === false) {
                    $this->client->delete($job);

                    if ($this->jobDelete !== null) {
                        call_user_func($this->jobDelete, $job);
                    }
                    continue;
                }

                $queueName = $data['queue'];
                $data = $data['data'];

                /* @var $queue Queue */
                $queue = $this->workerInstances[$queueName];
                $queue->process($data, $job);
                $this->client->delete($job);

                if ($this->jobDelete !== null) {
                    call_user_func($this->jobDelete, $job);
                }

            } catch (\Exception $e) {
                if ($this->error !== null) {
                    call_user_func($this->error, $e, $queueName);
                }

                $this->client->delete($job);
            }
        }
    }

}