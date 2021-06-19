<?php

declare(strict_types = 1);

namespace DanceEngineer\EventSauceProophEventStore;

use ArrayIterator;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\AggregateRootRepository;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageDecoratorChain;
use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\Serialization\SerializablePayload;
use EventSauce\EventSourcing\SynchronousMessageDispatcher;
use Generator;
use Iterator;
use Prooph\Common\Messaging\Message as StreamMessage;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Metadata\Operator;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Ramsey\Uuid\Uuid;

/**
 * @template T of AggregateRoot
 */
final class ProophEventStoreRepository implements AggregateRootRepository
{

    private const AGGREGATE_VERSION = '_aggregate_version';
    private const AGGREGATE_TYPE    = '_aggregate_type';
    private const AGGREGATE_ID      = '_aggregate_id';
    
    /** @psalm-var class-string<T> */
    private string $aggregateRootClassName;

    private MessageDispatcher $dispatcher;

    private MessageDecorator $decorator;

    private ConfiguredEventStore $configuredEventStore;

    /** @var array<callable(string, string, array<Message>):void> */
    public array $onNewEvents = [];

    /**
     * @psalm-param class-string<T> $aggregateRootClassName
     */
    public function __construct(
        string $aggregateRootClassName,
        ConfiguredEventStore $configuredEventStore,
        MessageDispatcher $dispatcher = null,
        MessageDecorator $decorator = null
    ) {
        $this->aggregateRootClassName = $aggregateRootClassName;
        $this->configuredEventStore = $configuredEventStore;
        $this->dispatcher             = $dispatcher ?? new SynchronousMessageDispatcher();
        $this->decorator              = $decorator
            ? new MessageDecoratorChain($decorator, new
            DefaultHeadersDecorator())
            : new
            DefaultHeadersDecorator();
    }

    /**
     * @psalm-return T
     */
    public function retrieve(AggregateRootId $aggregateRootId): object
    {
        $className = $this->aggregateRootClassName;
        $events    = $this->transformToEvents($this->retrieveStreamMessages($aggregateRootId));

        return $className::reconstituteFromEvents($aggregateRootId, $events);
    }

    /**
     * @param  \Iterator<StreamMessage>  $streamMessages
     * @return Generator<SerializablePayload>
     */
    private function transformToEvents(Iterator $streamMessages): Generator
    {
        $lastMessage = null;

        foreach ($streamMessages as $streamMessage) {
            assert($streamMessage instanceof TransientDomainMessage,
                'Expected $streamMessage to be an instance of '.TransientDomainMessage::class);

            /** @var SerializablePayload $messageName */
            $messageName = $streamMessage->messageName();
            yield $messageName::fromPayload($streamMessage->payload());
            $lastMessage = $streamMessage;
        }

        return $lastMessage instanceof TransientDomainMessage ? $lastMessage->metadata()[self::AGGREGATE_VERSION] : 0;
    }

    /**
     * @return \Iterator<StreamMessage>
     */
    private function retrieveStreamMessages(AggregateRootId $aggregateRootId): Iterator
    {
        $streamName = $this->streamNameFor($aggregateRootId);

        try {
            if ($this->configuredEventStore->oneStreamPerAggregate) {
                $streamEvents = $this->configuredEventStore->eventStore->load($streamName);
            } else {
                $metadataMatcher = (new MetadataMatcher())->withMetadataMatch(self::AGGREGATE_TYPE, Operator::EQUALS(),
                    $this->aggregateRootClassName)
                    ->withMetadataMatch(self::AGGREGATE_ID, Operator::EQUALS(), $aggregateRootId->toString());
                $streamEvents    = $this->configuredEventStore->eventStore->load($streamName, 1, null, $metadataMatcher);
            }

            if (!$streamEvents->valid()) {
                return new ArrayIterator([]);
            }

            return $streamEvents;
        } catch (StreamNotFound $e) {
            return new ArrayIterator([]);
        }
    }

    private function streamNameFor(AggregateRootId $aggregateRootId): StreamName
    {
        if ($this->configuredEventStore->oneStreamPerAggregate) {
            if ($this->configuredEventStore->streamName === null) {
                $prefix = $this->aggregateRootClassName;
            } else {
                $prefix = $this->configuredEventStore->streamName->toString();
            }

            return new StreamName($prefix.'-'.$aggregateRootId->toString());
        }

        return $this->configuredEventStore->streamName ?? new StreamName('event_stream');
    }

    public function persist(object $aggregateRoot): void
    {
        assert($aggregateRoot instanceof AggregateRoot,
            'Expected $aggregateRoot to be an instance of '.AggregateRoot::class);

        $this->persistEvents($aggregateRoot->aggregateRootId(), $aggregateRoot->aggregateRootVersion(), ...
            $aggregateRoot->releaseEvents());
    }

    public function persistEvents(AggregateRootId $aggregateRootId, int $aggregateRootVersion, object ...$events): void
    {
        $numberOfEvents = count($events);
        if ($numberOfEvents === 0) {
            return;
        }

        // decrease the aggregate root version by the number of raised events
        // so the version of each message represents the version at the time
        // of recording.
        $aggregateRootVersion -= $numberOfEvents;
        $metadata             = [Header::AGGREGATE_ROOT_ID => $aggregateRootId];
        $eventMessages        = array_map(function (object $event) use ($metadata, &$aggregateRootVersion) {
            return $this->decorator->decorate(new Message($event,
                $metadata + [Header::AGGREGATE_ROOT_VERSION => ++$aggregateRootVersion]));
        }, $events);

        $streamName     = $this->streamNameFor($aggregateRootId);
        $streamMessages = $this->transformToStreamMessages($eventMessages, $aggregateRootId);

        if (($this->configuredEventStore->oneStreamPerAggregate
             && $streamMessages[0]->metadata()[self::AGGREGATE_VERSION] === 1)
            || !$this->configuredEventStore->eventStore->hasStream($streamName)
        ) {
            $stream = new Stream($streamName, new ArrayIterator($streamMessages), $this->configuredEventStore->streamMetadata);
            $this->configuredEventStore->eventStore->create($stream);
        } else {
            $this->configuredEventStore->eventStore->appendTo($streamName, new ArrayIterator($streamMessages));
        }
        foreach ($this->onNewEvents as $callback) {
            $callback($this->aggregateRootClassName, $streamName->toString(), $eventMessages);
        }

        $this->dispatcher->dispatch(...$eventMessages);
    }

    /**
     * @param  array<Message>  $eventMessages
     * @return array<TransientDomainMessage>
     */
    private function transformToStreamMessages(
        array $eventMessages,
        AggregateRootId $aggregateRootId
    ): array {
        $streamMessages = [];
        foreach ($eventMessages as $eventMessage) {
            /** @var SerializablePayload $event */
            $event            = $eventMessage->event();
            $metadata         = [
                self::AGGREGATE_ID      => $aggregateRootId->toString(),
                self::AGGREGATE_TYPE    => $this->aggregateRootClassName,
                self::AGGREGATE_VERSION => $eventMessage->header(Header::AGGREGATE_ROOT_VERSION),
            ];
            $streamMessages[] = new TransientDomainMessage(Uuid::uuid4()
                ->toString(), get_class($event), $eventMessage->timeOfRecording()
                ->dateTime(), $metadata, $event->toPayload());
        }

        return $streamMessages;
    }

    public function setDispatcher(MessageDispatcher $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

}