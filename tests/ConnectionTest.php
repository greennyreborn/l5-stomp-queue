sd<?php

class ConnectionTest extends PHPUnit_Framework_TestCase {

    public function testConnect()
    {
        // make a connection
        $con = new \Stomp\StatefulStomp(new \Stomp\Client("tcp://localhost:61613"));

        $con->getClient()->connect();

        $this->assertTrue($con->getClient()->isConnected());
        $this->assertNotNull($con->getClient()->getSessionId());

        $con->send('test', new \Stomp\Transport\Message('hello'));

        $con->getClient()->disconnect();
    }

//    public function testConsume()
//    {
//        // make a connection
//        $con = new \Stomp\StatefulStomp(new \Stomp\Client("tcp://localhost:61613"));
//        $con->getClient()->getProtocol()->setPrefetchSize(1);
//
//        $con->subscribe('test');
//
//
//        $msg = $con->read();
//
//        if ( $msg != null) {
//            echo "Received message with body '$msg->body'\n";
//            // mark the message as received in the queue
//            $con->ack($msg);
//        } else {
//            echo "Failed to receive a message\n";
//        }
//
//        $con->getClient()->disconnect();
//    }
}