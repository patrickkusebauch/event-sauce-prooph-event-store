<?php

declare(strict_types = 1);

namespace DanceEngineer\EventSauceProophEventStore;

use DateTimeImmutable;
use DateTimeZone;
use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageFactory;
use Ramsey\Uuid\Uuid;
use UnexpectedValueException;

final class EventSauceMessageFactory implements MessageFactory
{

    /**
     * @param  array<mixed>  $messageData
     * @throws \UnexpectedValueException
     */
    public function createMessageFromArray(string $messageName, array $messageData): Message
    {
        if (!class_exists($messageName)) {
            throw new UnexpectedValueException('Given message name is not a valid class: '.$messageName);
        }
        if (!isset($messageData['message_name'])) {
            $messageData['message_name'] = $messageName;
        }

        if (!isset($messageData['uuid'])) {
            $messageData['uuid'] = Uuid::uuid4()
                ->toString();
        }

        if (!isset($messageData['created_at'])) {
            $messageData['created_at'] = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        }

        if (!isset($messageData['metadata'])) {
            $messageData['metadata'] = [];
        }

        return TransientDomainMessage::fromArray($messageData);
    }
}