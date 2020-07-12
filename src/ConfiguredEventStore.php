<?php

namespace DanceEngineer\EventSauceProophEventStore;

use Prooph\EventStore\EventStore;
use Prooph\EventStore\StreamName;

final class ConfiguredEventStore
{

    private EventStore $eventStore;

    private bool $oneStreamPerAggregate;

    /** @var array<mixed> */
    private array $streamMetadata;

    private ?StreamName $streamName;

    /**
     * @param  array<mixed>  $streamMetadata
     */
    public function __construct(
        EventStore $eventStore,
        bool $oneStreamPerAggregate = false,
        array $streamMetadata = [],
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

    /**
     * @return array<mixed>
     */
    public function streamMetadata(): array
    {
        return $this->streamMetadata;
    }

    public function streamName(): ?StreamName
    {
        return $this->streamName;
    }

}