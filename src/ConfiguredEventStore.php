<?php

namespace DanceEngineer\EventSauceProophEventStore;

use Prooph\EventStore\EventStore;
use Prooph\EventStore\StreamName;

final class ConfiguredEventStore
{

    private EventStore $eventStore;

    private bool $oneStreamPerAggregate;

    private array $streamMetadata;

    private ?StreamName $streamName;

    public function __construct(
        EventStore $eventStore, bool $oneStreamPerAggregate = false, array $streamMetadata = [],
        ?StreamName $streamName = null
    ) {
        $this->eventStore            = $eventStore;
        $this->oneStreamPerAggregate = $oneStreamPerAggregate;
        $this->streamMetadata        = $streamMetadata;
        $this->streamName            = $streamName;
    }

    public function eventStore(): EventStore
    {
        return $this->eventStore;
    }

    public function hasOneStreamPerAggregate(): bool
    {
        return $this->oneStreamPerAggregate;
    }

    public function streamMetadata(): array
    {
        return $this->streamMetadata;
    }

    public function streamName(): ?StreamName
    {
        return $this->streamName;
    }

}