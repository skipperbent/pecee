<?php

namespace Pecee\Queue;

use Pheanstalk\Contract\JobIdInterface;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Values\TubeName;

abstract class Queue
{

    abstract public function process(array $data, JobIdInterface $job): void;

    /**
     * @param array $data
     * @return JobIdInterface
     * @throws \JsonException
     */
    public function send(array $data = []): JobIdInterface
    {
        $pheanstalk = Worker::getClient();
        $tube = new TubeName($this->getQueue());

        // Queue a Job
        $pheanstalk->useTube($tube);
        return $pheanstalk->put(json_encode(
            [
                'queue' => $this->getQueue(),
                'data' => $data
            ],
            JSON_THROW_ON_ERROR,
        ));
    }

    public function getQueue(): string
    {
        return str_replace('\\', '.', basename(get_class($this)));
    }

}