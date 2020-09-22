<?php
/**
 * Created by PhpStorm.
 * User: Yan
 * DateTime: 2020/9/22 20:15
 */
//测试发现广播模式消息不能持久化
require_once '../vendor/autoload.php';

use RabbitMQ\RabbitMQ;

$exchangeName = "fanout_exchange";

$rabbit = new RabbitMQ();
for ($i = 0; $i < 10; $i++) {
    $message = [
        'hello' . $i,
    ];
    $rabbit->publish(json_encode($message), '', $exchangeName,true);
}

