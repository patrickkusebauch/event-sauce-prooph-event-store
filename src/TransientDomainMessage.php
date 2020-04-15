<?php

namespace DanceEngineer\EventSauceProophEventStore;

use DateTimeImmutable;
use Prooph\Common\Messaging\DomainEvent;
use Ramsey\Uuid\Uuid;

final class TransientDomainMessage extends DomainEvent
{

    protected array $payload = [];

    public function __construct(
        string $uuid, string $event, DateTimeImmutable $recordedAt, array $metadata, array $payload
    ) {
        $this->uuid        = Uuid::fromString($uuid);
        $this->messageName = $event;
        $this->createdAt   = $recordedAt;
        $this->metadata    = $metadata;
        $this->payload     = $payload;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    protected function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }
}