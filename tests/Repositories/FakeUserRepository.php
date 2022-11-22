<?php

declare(strict_types=1);

namespace Ybelenko\OAuth2\Server\Repositories;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use Ybelenko\OAuth2\Server\Entities\FakeUserEntity;

final class FakeUserRepository implements UserRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getUserEntityByUserCredentials(
        $username,
        $password,
        $grantType,
        ClientEntityInterface $clientEntity
    ) {
        if (
            $grantType === 'custom_email'
            && is_string($username)
            && in_array(strtolower($username), ['fake1_email@example.dev', 'fake2_email@example.dev', 'fake_blacklisted@example.dev'], true)
        ) {
            $user = new FakeUserEntity();
            $user->setIdentifier($username);
            return $user;
        }

        return null;
    }
}
