<?php

namespace Eventio\BBQBundle\EventListener;

use Eventio\BBQBundle\DeferringOuterQueue;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Console\ConsoleEvents;

class DeferredQueueListener implements EventSubscriberInterface
{

    protected $queues = array();
    
    protected $logger;
    
    protected $container;
    
    public function __construct($container, $logger) {
        $this->container = $container;
        $this->logger = $logger;
    }

    public function onKernelTerminate() {
        $this->flush();
    }
    
    public function onConsoleTerminate() {
        $this->flush();
    }
    
    public function onRequestFlush() {
        $this->flush();
    }
    
    private function flush() {
        $queues = $this->queues;
        $this->logger->debug('DeferredQueueListener::flush() - ' . count($queues) . ' queue(s).');
        foreach ($queues as $q) {
            $q->flush($this->logger);
        }
    }

    public function addQueue($queue) {
        $this->queues[] = $queue;
    }
    
    public static function getSubscribedEvents() {
        return array(
            KernelEvents::TERMINATE  => 'onKernelTerminate',
            ConsoleEvents::TERMINATE => 'onConsoleTerminate',
            'bbq.request_flush' => 'onRequestFlush',
        );
    }

}