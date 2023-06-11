<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Admin\Events;

use EnjoysCMS\Core\Users\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class BeforeDeleteUserEvent extends Event
{
    public const NAME = 'before.delete.user';

    public function __construct(private readonly User $user)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
