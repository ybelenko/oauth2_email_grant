<?php

declare(strict_types=1);

namespace Ybelenko\OAuth2\Server\Entities;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\ScopeTrait;

final class FakeScopeEntity implements ScopeEntityInterface
{
    use EntityTrait;
    use ScopeTrait;
}
