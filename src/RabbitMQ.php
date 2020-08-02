<?php


namespace RabbitMQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMQ
{

    private $host = '127.0.0.1';
    private $port = 5672;
    private $user = 'guest';
    private $password = 'guest';
    private $vhost = '/';
    protected $connection;
    protected $channel;


    /**
     * src constructor.
     */
    public function __construct()
    {
        if(function_exists('config')){
            $this->host = config('rabbitmq.host');
            $this->port = config('rabbitmq.port');
            $this->vhost = config('rabbitmq.vhost');
            $this->user = config('rabbitmq.login');
            $this->password = config('rabbitmq.password');
        }

        $this->connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password,$this->vhost);
        $this->channel = $this->connection->channel();
    }

    /**
     * @return \PhpAmqpLib\Channel\AMQPChannel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param $exchangeName
     * @param $type
     * @param $pasive
     * @param $durable
     * @param $autoDelete
     */
    public function createExchange($exchangeName, $type, $pasive = false, $durable = false, $autoDelete = false)
    {
        $this->channel->exchange_declare($exchangeName, $type, $pasive, $durable, $autoDelete);
    }

    /**
     * @param $queueName
     * @param $pasive
     * @param $durable
     * @param $exlusive
     * @param $autoDelete
     */
    public function createQueue($queueName, $pasive = false, $durable = false, $exlusive = false, $autoDelete = false, $nowait = false, $arguments = [])
    {
        $this->channel->queue_declare($queueName, $pasive, $durable, $exlusive, $autoDelete, $nowait, $arguments);
    }

    /**
     * 创建延时队列
     * @param $ttl
     * @param $delayExName
     * @param $delayQueueName
     * @param $queueName
     */
    public function createDelayQueue($ttl, $delayExName, $delayQueueName, $queueName)
    {
        $args = new AMQPTable([
            'x-dead-letter-exchange' => $delayExName,
            'x-message-ttl' => $ttl, //消息存活时间
            'x-dead-letter-routing-key' => $queueName
        ]);
        $this->channel->queue_declare($queueName, false, true, false, false, false, $args);
        //绑定死信queue
        $this->channel->exchange_declare($delayExName, AMQPExchangeType::DIRECT, false, true, false);
        $this->channel->queue_declare($delayQueueName, false, true, false, false);
        $this->channel->queue_bind($delayQueueName, $delayExName, $queueName, false);
    }

    /**
     * @param $exchangeName
     * @param $queue
     * @param string $routing_key
     * @param bool $nowait
     * @param array $arguments
     * @param null $ticket
     */
    public function bindQueue($queue, $exchangeName, $routing_key = '',
                              $nowait = false,
                              $arguments = array(),
                              $ticket = null)
    {
        $this->channel->queue_bind($queue, $exchangeName, $routing_key, $nowait, $arguments, $ticket);
    }

    /**
     * 生成信息
     * @param $message
     */
    public function sendMessage($message, $routeKey, $exchange = '', $properties = [])
    {
        $data = new AMQPMessage(
            $message, $properties
        );
        $this->channel->basic_publish($data, $exchange, $routeKey);
    }

    /**
     * @notes:发布消息
     * @param $message
     * @param $routeKey
     * @param string $exchange
     * @param bool $durable
     * @param array $properties
     * @author: Yan
     * @dataTime: 2020/8/2 18:19
     */
    public function publish($message, $routeKey, $exchange = '', $durable = true, $properties = [])
    {
        $property = [];
        if ($durable) {
            $property = [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT //消息持久化，重启rabbitmq，消息不会丢失
            ];
        }
        $properties = array_merge($property, $properties);
        $data = new AMQPMessage(
            $message, $properties
        );
        $this->channel->basic_publish($data, $exchange, $routeKey);
    }

    /**
     * 消费消息
     * @param $queueName
     * @param $callback
     * @throws \ErrorException
     */
    public function consumeMessage($queueName, $callback, $tag = '', $noLocal = false, $noAck = false, $exclusive = false, $noWait = false)
    {
        //只有consumer已经处理并确认了上一条message时queue才分派新的message给它
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($queueName, $tag, $noLocal, $noAck, $exclusive, $noWait, $callback);
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    /**
     * @notes:消息应答
     * @param $msg
     * @author: Yan
     * @dataTime: 2020/8/2 18:24
     */
    public function ack($msg)
    {
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    }

    /**
     * @throws \Exception
     */
    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
