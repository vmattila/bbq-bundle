<?php

namespace Eventio\BBQBundle;

use Eventio\BBQ\Job\Job;
use Eventio\BBQ\Job\JobInterface;
use Eventio\BBQ\Job\Payload\JobPayloadInterface;
use Eventio\BBQ\Queue\AbstractQueue;
use Eventio\BBQ\Queue\QueueInterface;

class DeferringOuterQueue extends AbstractQueue
{
    /**
     * @var QueueInterface
     */
    protected $innerQueue;
    
    public function __construct($id, QueueInterface $innerQueue) {
        parent::__construct($id);

        $this->innerQueue = $innerQueue;
    }
    
    public function init() {
        // Nothing
    }

    public function finalizeJob(JobInterface $job)
    {
        $this->innerQueue->finalizeJob($job);
    }
    
    public function mayHaveJob()
    {
        return ($this->innerQueue->mayHaveJob() || count($this->deferredJobs) > 0);
    }

    public function fetchJob($timeout = null)
    {
        return $this->innerQueue->fetchJob($timeout);
    }
    
    protected $deferredJobs = array();
    
    public function pushJob(JobPayloadInterface $jobPayload)
    {
        $this->deferredJobs[] = $jobPayload;
    }

    public function flush($logger = null) {
        if ($logger) {
            $logger->debug(sprintf('DeferringOuterQueue::flush() for %s [%s] called with %01d job(s).', get_class($this->innerQueue), $this->id, count($this->deferredJobs)));
        }
        foreach ($this->deferredJobs as $job) {
            $this->innerQueue->pushJob($job);
        }
        $this->clear();
    }
    
    public function clear() {
        $this->deferredJobs = array();
    }
    
    public function releaseJob(JobInterface $job)
    {
        return $this->innerQueue->releaseJob($job);
    }

    public function keepAlive(JobInterface $job)
    {
        return $this->innerQueue->keepAlive($job);
    }
}