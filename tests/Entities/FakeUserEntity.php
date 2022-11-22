<?php

declare(strict_types=1);

namespace Ybelenko\OAuth2\Server\Entities;

use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\UserEntityInterface;

final class FakeUserEntity implements UserEntityInterface
{
    use EntityTrait;
}
