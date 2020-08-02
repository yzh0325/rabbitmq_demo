<?php

use RabbitMQ\Delay\DelayQueue;
use RabbitMQ\RabbitMQ;

require_once '../vendor/autoload.php';
// 消费者
$ttl            = 1000 * 5;//订单100s后超时
$delayExName    = 'delay-order-exchange';//超时exchange
$delayQueueName = 'delay-order-queue';//超时queue
$queueName      = 'ttl-order-queue';//订单queue

$delay = new RabbitMQ();

$delayQueueName = 'delay-order-queue';

$delay->createDelayQueue($ttl, $delayExName, $delayQueueName, $queueName);

$callback = function ($msg) {
    echo $msg->body . PHP_EOL;
    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

    //处理订单超时逻辑，给用户推送提醒等等。。。
    sleep(1);
};

/**
 * 消费已经超时的订单信息，进行处理
 */
$delay->consumeMessage($delayQueueName, $callback);


