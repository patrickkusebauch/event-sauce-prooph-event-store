# event-sauce-prooph-event-store
A repository for EventSauce that connects to a Prooph Event Store 

## Usage
```php

function createInGeneral(): \EventSauce\EventSourcing\AggregateRootRepository
{
    /** @var \Prooph\EventStore\EventStore $eventStore */
    // Your Event Store has implementation has to use EventSauceMessageFactory to create messages from data!!

    $configuredEventStore = new ConfiguredEventStore($eventStore);
    return new ProophEventStoreRepository(\EventSauce\EventSourcing\AggregateRoot::class, $configuredEventStore);
}

function createConcreteExample(): \EventSauce\EventSourcing\AggregateRootRepository
{
    $eventStore = new \Prooph\EventStore\Pdo\PostgresEventStore(
        new EventSauceMessageFactory(), // Your Event Store has to use this class to create messages from data!!
        new \PDO('you know how to configure a PDO connection, don\'t you?'),
        new \Prooph\EventStore\Pdo\PersistenceStrategy\PostgresAggregateStreamStrategy()
    );

    $configuredEventStore = new ConfiguredEventStore($eventStore, true);
    return new ProophEventStoreRepository(\EventSauce\EventSourcing\AggregateRoot::class, $configuredEventStore);
}

function createFullConfig(): \EventSauce\EventSourcing\AggregateRootRepository
{
    /** @var \Prooph\EventStore\EventStore $eventStore */
    // Your Event Store has implementation has to use EventSauceMessageFactory to create messages from data!!

    $configuredEventStore = new ConfiguredEventStore($eventStore, false, ['stream_tag' => 'banana'], new
    \Prooph\EventStore\StreamName('lama'));
    return new ProophEventStoreRepository(\EventSauce\EventSourcing\AggregateRoot::class, $configuredEventStore, new
    \EventSauce\EventSourcing\CollectingMessageDispatcher(), new \EventSauce\EventSourcing\DefaultHeadersDecorator());
}
```