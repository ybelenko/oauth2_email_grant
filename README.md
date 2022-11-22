# OAuth 2.0 Email Grant(Custom)

[![Tests](https://github.com/ybelenko/oauth2_email_grant/actions/workflows/main.yml/badge.svg)](https://github.com/ybelenko/oauth2_email_grant/actions/workflows/main.yml)
[![Coverage Status](https://coveralls.io/repos/github/ybelenko/oauth2_email_grant/badge.svg?branch=main)](https://coveralls.io/github/ybelenko/oauth2_email_grant?branch=main)

## Requirements
* PHP 8.x

## Important Notice
> If you decide to extend some of the classes make sure you **DON'T expose access token** somewhere.
> Check that you DON'T `echo/print/var_dump` access token or instance of it.

## Installation via [Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos)
Run in command line:
```console
composer require ybelenko/oauth2_email_grant
```

## Basic Usage

Assuming you already have [PHP League OAuth 2.0 Server](https://oauth2.thephpleague.com) installed and configured.

```php
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use Ybelenko\OAuth2\Server\Grant\CustomEmailGrant;

// if you want to use class method instead of anonymous functions
// do $onAccessToken = \Closure::fromCallable([$this, 'onAccessToken'])
// or when method is static $onAccessToken = \Closure::fromCallable([UserClass::class, 'onAccessToken'])

$grant = new CustomEmailGrant(
    $userRepository, // repository used in your oauth2 server implementation
    static function (
        string $email,
        AccessTokenEntityInterface $accessToken,
        ClientEntityInterface $client, 
        UserEntityInterface $user,
        array $scopes
    ) {
        // send access token to user via email
        // or do something else
    },
    static function (string $email) {
        // validate email the way you want
        // throw an exception or return true|false
        // everything beside true return will stop token creation
    }
);

// all other repos should be added from auth server automatically
// right after you call
$server->enableGrantType($grant, new \DateInterval('PT1H'));
```

then in your `UserRepositoryInterface` implementation handle new custom grant:
```php
/**
 * {@inheritdoc}
 */
public function getUserEntityByUserCredentials(
    $username,
    $password,
    $grantType,
    ClientEntityInterface $clientEntity
) {
    if ($grantType === 'custom_email') {
        // password is empty string now
        // just for readability
        $email = $username;
        // if user with provided email exists return new entity
        // otherwise return null
        // don't need to check password since we send token
        // to provided email, works like common account recover flow
        $user = new FakeUserEntity();
        $user->setIdentifier($email);
        return $user;
    }

    // handle other grants also check password from now

    return null;
}
```

To use new grant user can send POST with a small change(`grant_type` changed to `custom_email`, new field `email`):
```json
{
    "grant_type": "custom_email",
    "client_id": "client",
    "client_secret": "secret",
    "email": "johndoe@example.dev",
    "scope": "foo baz bar"
}
```
it's also possible to send `client_id` and `client_secret` as `"Authorization: Basic {base64_encode($clientId . ':' . $clientSecret)}"` HTTP header.

Instead of usual access token response user receives payload like:
```json
{
    "message": "Mail with recover link has been sent to provided address",
    "expires_in": 3600
}
```

## Author
[Yuriy Belenko](https://github.com/ybelenko)
