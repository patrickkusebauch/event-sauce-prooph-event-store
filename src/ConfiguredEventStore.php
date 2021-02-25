<?php

namespace DanceEngineer\EventSauceProophEventStore;

use Prooph\EventStore\EventStore;
use Prooph\EventStore\StreamName;

/**
 * @psalm-immutable 
 */
final class ConfiguredEventStore
{

    public EventStore $eventStore;

    public bool $oneStreamPerAggregate;

    /** @var array<mixed> */
    public array $streamMetadata;

    public ?StreamName $streamName;

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

}