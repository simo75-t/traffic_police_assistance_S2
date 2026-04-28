<?php

namespace App\Http\Services\PoliceOfficer;

use App\Integrations\Messaging\RabbitMqPublisher as MessagingRabbitMqPublisher;

class RabbitPublisher
{
    public function __construct(
        private readonly MessagingRabbitMqPublisher $publisher,
    ) {
    }

    public function publish(string $routingKey, array $data, string $queueName): void
    {
        $this->publisher->publish($routingKey, $data, $queueName);
    }
}
