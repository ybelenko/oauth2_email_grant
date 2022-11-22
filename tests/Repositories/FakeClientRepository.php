<?php

declare(strict_types=1);

namespace Ybelenko\OAuth2\Server\Repositories;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Ybelenko\OAuth2\Server\Entities\FakeClientEntity;

final class FakeClientRepository implements ClientRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getClientEntity($clientIdentifier)
    {
        return ($clientIdentifier === 'Aladdin') ? new FakeClientEntity() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function validateClient($clientIdentifier, $clientSecret, $grantType)
    {
        return $clientIdentifier === 'Aladdin' && $clientSecret === 'open sesame';
    }
}
