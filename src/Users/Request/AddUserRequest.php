<?php

namespace EnjoysCMS\Module\Admin\Users\Request;

use Yiisoft\Hydrator\Validator\ValidatedInputInterface;
use Yiisoft\Hydrator\Validator\ValidatedInputTrait;
use Yiisoft\Validator\Rule\Required;

final class AddUserRequest implements ValidatedInputInterface
{
    use ValidatedInputTrait;

    public function __construct(
        #[Required]
        public readonly string $name = '',
        #[Required]
        public readonly string $login = '',
        #[Required]
        public readonly string $password = '',
        public readonly array $groups = [],
    ) {
    }
}