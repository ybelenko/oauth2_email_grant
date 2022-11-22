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

## Author
[Yuriy Belenko](https://github.com/ybelenko)
