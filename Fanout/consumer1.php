<?php
/**
 * Created by PhpStorm.
 * User: Yan
 * DateTime: 2020/9/22 20:14
 */

require_once '../vendor/autoload.php';
use PhpAmqpLib\Exchange\AMQPExchangeType;
use RabbitMQ\RabbitMQ;
$exchangeName = "fanout_exchange";
$queuename =
$rabbit = new RabbitMQ();
//创建交换机
$rabbit->createExchange($exchangeName, AMQPExchangeType::FANOUT, false, true, false);
//创建队列
$queue =  $rabbit->createQueue('', false, true,true);
//绑定到交换机
$rabbit->bindQueue($queue, $exchangeName);

$rabbit->consumeMessage($queue, 'process_message');

function process_message($message){
    global $rabbit;
    $msg = $message->body;
    var_dump($msg);
    return $rabbit->ack($message);
}