<?php

use Mayconbordin\L5StompQueue\StompQueue;
use Mockery as m;

class StompQueueTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Mockery\MockInterface
     */
    protected $stomp;

    /**
     * @var StompQueue
     */
    protected $queue;

    protected function setUp()
    {
        $this->stomp = m::mock(\Stomp\StatefulStomp::class);

        $this->queue = new StompQueue($this->stomp, 'test', StompQueue::SYSTEM_ACTIVEMQ);

        $container = m::mock(\Illuminate\Container\Container::class);
        $this->queue->setContainer($container);
    }

    protected function tearDown()
    {
        parent::tearDown();
        m::close();
    }


    public function testPush()
    {
        $job   = 'job';
        $data  = 'data';
        $queue = 'test';

        $body = json_encode(['job' => $job, 'data' => $data]);

        $this->stomp->shouldReceive('send')->once()->andReturnUsing(function ($arg1, \Stomp\Transport\Message $arg2) use ($queue, $body) {
            $this->assertEquals($queue, $arg1);
            $this->assertEquals($body, $arg2->getBody());
        });
        $this->queue->push($job, $data);
    }

    public function testPushRaw()
    {
        $data  = 'data';
        $queue = 'test';
        $headers = ['delay' => 10];

        $this->stomp->shouldReceive('send')->once()->andReturnUsing(function ($arg1, \Stomp\Transport\Message $arg2) use ($data, $queue) {
            $this->assertEquals($queue, $arg1);
            $this->assertEquals($data, $arg2->getBody());
        });
        $this->queue->pushRaw($data, $queue);

        $this->stomp->shouldReceive('send')->once()->andReturnUsing(function ($arg1, \Stomp\Transport\Message $arg2) use ($data, $queue, $headers) {
            $this->assertEquals($queue, $arg1);
            $this->assertEquals($data, $arg2->getBody());
            $this->assertEquals($headers, $arg2->getHeaders());
        });
        $this->queue->pushRaw($data, $queue, $headers);
    }

    public function testRecreate()
    {
        $data  = 'data';
        $queue = 'test';

        $this->stomp->shouldReceive('send')->once()->andReturnUsing(function ($arg1, \Stomp\Transport\Message $arg2) use ($data, $queue) {
            $this->assertEquals($queue, $arg1);
            $this->assertEquals($data, $arg2->getBody());
            $this->assertEquals(['AMQ_SCHEDULED_DELAY' => 0], $arg2->getHeaders());
        });
        $this->queue->recreate($data, $queue, 0);
    }

    public function testLater()
    {
        $job   = 'job';
        $data  = 'data';
        $queue = 'test';

        $body = json_encode(['job' => $job, 'data' => $data]);

        $this->stomp->shouldReceive('send')->once()->andReturnUsing(function ($arg1, \Stomp\Transport\Message $arg2) use ($data, $queue, $body) {
            $this->assertEquals($queue, $arg1);
            $this->assertEquals($body, $arg2->getBody());
            $this->assertEquals(['AMQ_SCHEDULED_DELAY' => 10000], $arg2->getHeaders());
        });
        $this->queue->later(10, $job, $data, $queue);
    }

    public function testPop()
    {
        $queue = 'test';
        $body = ['job' => 'job-1', 'queue' => $queue, 'attempts' => 1];
        $message = new \Stomp\Transport\Frame(null, [], json_encode($body));

        $this->stomp->shouldReceive('subscribe')->once()->with($queue);
        $this->stomp->shouldReceive('read')->once()->andReturn($message);

        $job = $this->queue->pop($queue);

        $this->assertEquals($body['job'], $job->getName());
        $this->assertEquals($body['queue'], $job->getQueue());
        $this->assertEquals(json_encode($body), $job->getRawBody());
    }

    public function testDeleteMessage()
    {
        $body = ['job' => 'job-1', 'queue' => 'test', 'attempts' => 1];
        $message = new \Stomp\Transport\Frame(null, [], json_encode($body));

        $this->stomp->shouldReceive('ack')->once()->with($message);

        $this->queue->deleteMessage('test', $message);
    }

}