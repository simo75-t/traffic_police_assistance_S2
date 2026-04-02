<?php

namespace App\Http\Services\PoliceOfficer;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitPublisher
{
    public function publish(string $routingKey, array $data, string $queueName): void
    {
        $conn = new AMQPStreamConnection(
            config('rabbitmq.host', '127.0.0.1'),
            config('rabbitmq.port', 5672),
            config('rabbitmq.user', 'admin'),
            config('rabbitmq.password', 'admin123'),
            config('rabbitmq.vhost', '/')
        );

        $ch = $conn->channel();
        $exchange = config('ai_rmq.exchange');

        $ch->exchange_declare($exchange, 'direct', false, true, false);
        $ch->queue_declare($queueName, false, true, false, false);
        $ch->queue_bind($queueName, $exchange, $routingKey);

        $msg = new AMQPMessage(
            json_encode($data, JSON_UNESCAPED_UNICODE),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'correlation_id' => $data['correlation_id'] ?? null,
            ]
        );

        $ch->basic_publish($msg, $exchange, $routingKey);

        $ch->close();
        $conn->close();
    }
}
