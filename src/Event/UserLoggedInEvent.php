<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\User;

final readonly class UserLoggedInEvent
{
    public function __construct(
        public User $user,
    ) {
    }
}
