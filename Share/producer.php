<?php

require_once '../vendor/autoload.php';

use PhpAmqpLib\Message\AMQPMessage;
use RabbitMQ\RabbitMQ;


$rabbit  = new RabbitMQ();
$channel = $rabbit->getChannel();

$queueName = 'test-single-queue';
$rabbit->createQueue($queueName, false, true, false, false);

for ($i = 0; $i < 10000; $i++) {
    $rabbit->sendMessage($i . "this is a test message.", $queueName, '', [
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT //消息持久化，重启rabbitmq，消息不会丢失
    ]);
}

unset($rabbit);//关闭连接
