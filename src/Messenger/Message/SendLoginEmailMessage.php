<?php

declare(strict_types=1);

namespace App\Messenger\Message;

final class SendLoginEmailMessage
{
    public function __construct(
        public string $email,
        public string $name,
    ) {
    }
}
