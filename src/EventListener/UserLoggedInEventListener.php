<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\UserLoggedInEvent;
use App\Messenger\Message\SendLoginEmailMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener]
class UserLoggedInEventListener
{
    public function __construct(
        private MessageBusInterface $bus
    ) {
    }

    public function __invoke(UserLoggedInEvent $event): void
    {
        $this->bus->dispatch(
            new SendLoginEmailMessage(
                $event->user->getEmail(),
                $event->user->getName(),
            )
        );
    }
}
