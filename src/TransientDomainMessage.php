<?php

namespace DanceEngineer\EventSauceProophEventStore;

use DateTimeImmutable;
use Prooph\Common\Messaging\DomainEvent;
use Ramsey\Uuid\Uuid;

final class TransientDomainMessage extends DomainEvent
{

    /** @var array<mixed> */
    protected array $payload = [];

    /**
     * @param  array<mixed>  $payload
     * @param  array<mixed>  $metadata
     */
    public function __construct(
        string $uuid,
        string $event,
        DateTimeImmutable $recordedAt,
        array $metadata,
        array $payload
    ) {
        $this->uuid        = Uuid::fromString($uuid);
        $this->messageName = $event;
        $this->createdAt   = $recordedAt;
        $this->metadata    = $metadata;
        $this->payload     = $payload;
    }

    /**
     * @return array<mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    /**
     * @param  array<mixed>  $payload
     */
    protected function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }
}