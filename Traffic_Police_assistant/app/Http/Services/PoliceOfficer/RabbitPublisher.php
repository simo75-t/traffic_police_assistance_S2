<?php

namespace App\Http\Services\PoliceOfficer;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitPublisher
{
    public function publish(string $routingKey, array $data, string $queueName): void
    {
        $host = config('queue.connections.rabbitmq.host', env('RABBITMQ_HOST', '127.0.0.1'));
        $port = (int) config('queue.connections.rabbitmq.port', env('RABBITMQ_PORT', 5672));
        $user = config('queue.connections.rabbitmq.user', env('RABBITMQ_USER', 'admin'));
        $password = config('queue.connections.rabbitmq.password', env('RABBITMQ_PASSWORD', 'admin123'));
        $vhost = config('queue.connections.rabbitmq.vhost', env('RABBITMQ_VHOST', '/'));
        $exchange = config('ai_rmq.exchange', env('AI_RMQ_EXCHANGE', 'ai.exchange'));

        logger()->info('Rabbit publish attempt', [
            'exchange' => $exchange,
            'queue' => $queueName,
            'routing_key' => $routingKey,
            'job_id' => $data['job_id'] ?? $data['request_id'] ?? null,
            'host' => $host,
            'port' => $port,
            'vhost' => $vhost,
        ]);

        $conn = new AMQPStreamConnection(
            $host,
            $port,
            $user,
            $password,
            $vhost
        );

        $ch = $conn->channel();

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

        logger()->info('Rabbit publish success', [
            'exchange' => $exchange,
            'queue' => $queueName,
            'routing_key' => $routingKey,
            'job_id' => $data['job_id'] ?? $data['request_id'] ?? null,
        ]);

        $ch->close();
        $conn->close();
    }
}
