<?php

declare(strict_types=1);

namespace Ybelenko\OAuth2\Server\Entities;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

final class FakeClientEntity implements ClientEntityInterface
{
    use ClientTrait;
    use EntityTrait;
}
