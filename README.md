# event-sauce-prooph-event-store
A repository for EventSauce that connects to a Prooph Event Store.

## Why?
Prooph components are very opiniated about how you should write your code. 

Most of the components for event-sourcing are [deprecated now](https://www.sasaprolic.com/2018/08/the-future-of-prooph-components.html) and so not work with the few component that are still being developed and are maintained.

One of the few that are still maintained is the interface forpersistance into an `event store`. With it also many implememntations like `PDO` and `Event Store` database. Furthermore they offer several useful strategies for segmentation of events in the event store.

Therefore it is useful to do your event sourcing with `EventSauce` and to your event storage with `Prooph`. This library offers you just that. Take advantage of `Prooph` comprehensive persistance layer. 

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
