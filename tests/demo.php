<?php
/**
 * Created by IntelliJ IDEA.
 * User: michael
 * Date: 09/12/2016
 * Time: 1:24 AM
 */

require __DIR__.'/../vendor/autoload.php';

// make a connection
$con = new \Stomp\StatefulStomp(new \Stomp\Client("tcp://localhost:61613"));
$con->getClient()->getProtocol()->setPrefetchSize(10);

$con->subscribe('test');


$msg = $con->read();

if ( $msg != null) {
    echo "Received message with body '$msg->body'\n";
    // mark the message as received in the queue
    $con->ack($msg);
} else {
    echo "Failed to receive a message\n";
}
