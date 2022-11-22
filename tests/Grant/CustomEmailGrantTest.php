<?php

declare(strict_types=1);

namespace Ybelenko\OAuth2\Server\ResponseTypes;

use Closure;
use GuzzleHttp\Psr7\HttpFactory;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Ybelenko\OAuth2\Server\Grant\CustomEmailGrant;
use Ybelenko\OAuth2\Server\Repositories\FakeAccessTokenRepository;
use Ybelenko\OAuth2\Server\Repositories\FakeClientRepository;
use Ybelenko\OAuth2\Server\Repositories\FakeScopeRepository;
use Ybelenko\OAuth2\Server\Repositories\FakeUserRepository;

/**
 * @coversDefaultClass \Ybelenko\OAuth2\Server\Grant\CustomEmailGrant
 *
 * @internal
 *
 * @small
 */
final class CustomEmailGrantTest extends TestCase
{
    protected CustomEmailGrant $grant;

    protected ?string $email = null;
    protected ?AccessTokenEntityInterface $accessToken = null;
    protected ?ClientEntityInterface $client = null;
    protected ?UserEntityInterface $user = null;
    /** @var ScopeEntityInterface[] */
    protected ?array $scopes = null;

    protected function setUp(): void
    {
        $this->email = null;
        $this->accessToken = null;
        $this->client = null;
        $this->user = null;
        $this->scopes = null;
        $this->grant = new CustomEmailGrant(new FakeUserRepository(), Closure::fromCallable([$this, 'onAccessTokenIssued']));
        $this->grant->setPrivateKey(new CryptKey(realpath(__DIR__ . '/../fake_private.key')));
        $this->grant->setClientRepository(new FakeClientRepository());
        $this->grant->setScopeRepository(new FakeScopeRepository());
        $this->grant->setAccessTokenRepository(new FakeAccessTokenRepository());
        $this->grant->setDefaultScope('foobar');
    }

    /**
     * @covers ::__construct
     * @covers ::respondToAccessTokenRequest
     * @covers ::setOnAccessTokenIssued
     * @covers ::getIdentifier
     * @dataProvider provideRequests
     */
    public function testRespondToAccessTokenRequest(
        ServerRequestInterface $req,
        ResponseTypeInterface $responseType
    ): void {
        $tokenTtl = new \DateInterval('PT15M');
        $response = $this->grant->respondToAccessTokenRequest($req, $responseType, $tokenTtl);

        static::assertInstanceOf(MailSentResponse::class, $response);

        // check that callback saved values
        static::assertSame('fake1_email@example.dev', $this->email);
        static::assertInstanceOf(AccessTokenEntityInterface::class, $this->accessToken);
        static::assertInstanceOf(UserEntityInterface::class, $this->user);
        static::assertInstanceOf(ClientEntityInterface::class, $this->client);
        static::assertIsArray($this->scopes);
        static::assertSame('foobar', $this->scopes[0]->getIdentifier());
    }

    public function provideRequests(): array
    {
        $factory = new HttpFactory();
        $clientCredentials = \base64_encode('Aladdin:open sesame');
        $req = $factory->createServerRequest('POST', 'https://example.dev/token')
            ->withHeader('Authorization', "Basic {$clientCredentials}")
            ->withHeader('Content-Type', 'application/json');

        return [
            'correct request' => [
                $req->withParsedBody([
                    'grant' => 'custom_email',
                    'email' => 'fake1_email@example.dev',
                ]),
                new BearerTokenResponse(),
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::respondToAccessTokenRequest
     * @covers ::getIdentifier
     * @dataProvider provideWrongRequests
     */
    public function testCatchExceptions(
        ServerRequestInterface $req
    ): void {
        $this->expectException(OAuthServerException::class);

        $tokenTtl = new \DateInterval('PT15M');
        $this->grant->respondToAccessTokenRequest($req, new MailSentResponse(), $tokenTtl);
    }

    public function provideWrongRequests(): array
    {
        $factory = new HttpFactory();
        $clientCredentials = \base64_encode('Aladdin:open sesame');
        $johnAuth = \base64_encode('johndoe:foobar');
        $req = $factory->createServerRequest('POST', 'https://example.dev/token')
            ->withHeader('Authorization', "Basic {$clientCredentials}")
            ->withHeader('Content-Type', 'application/json');

        return [
            'unknown user' => [
                $req->withParsedBody([
                    'grant' => 'custom_email',
                    'email' => 'fake_unknown@example.dev',
                ]),
            ],
            'missed email field' => [
                $req->withParsedBody([
                    'grant' => 'custom_email',
                ]),
            ],
            'email not a string' => [
                $req->withParsedBody([
                    'grant' => 'custom_email',
                    'email' => 1234,
                ]),
            ],
            'empty email' => [
                $req->withParsedBody([
                    'grant' => 'custom_email',
                    'email' => '',
                ]),
            ],
            'unknown client' => [
                $req->withParsedBody([
                    'grant' => 'custom_email',
                    'email' => 'fake1_email@example.dev',
                ])
                ->withHeader('Authorization', "Basic {$johnAuth}"),
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::respondToAccessTokenRequest
     * @covers ::setOnAccessTokenIssued
     * @covers ::getIdentifier
     * @dataProvider provideOnAccessTokenCallbacks
     */
    public function testOnAccessTokenCallback(
        ServerRequestInterface $req,
        ?\Closure $callback = null,
        ?string $exClass = null
    ): void {
        if (!is_null($exClass)) {
            $this->expectException($exClass);
        }

        $tokenTtl = new \DateInterval('PT15M');
        if (!is_null($callback)) {
            $this->grant->setOnAccessTokenIssued($callback);
        }

        $response = $this->grant->respondToAccessTokenRequest($req, new MailSentResponse(), $tokenTtl);

        if (is_null($exClass)) {
            static::assertInstanceOf(MailSentResponse::class, $response);
        }
    }

    public function provideOnAccessTokenCallbacks(): array
    {
        $factory = new HttpFactory();
        $clientCredentials = \base64_encode('Aladdin:open sesame');
        $req = $factory->createServerRequest('POST', 'https://example.dev/token')
            ->withParsedBody([
                'grant' => 'custom_email',
                'email' => 'fake1_email@example.dev',
            ])
            ->withHeader('Authorization', "Basic {$clientCredentials}")
            ->withHeader('Content-Type', 'application/json');

        return [
            'correct request' => [
                $req,
            ],
            'throw exception in callback' => [
                $req,
                static function () {
                    throw new \LogicException('Exception after token issued');
                },
                \LogicException::class,
            ],
            'throw exception in callback from static method' => [
                $req,
                \Closure::fromCallable([CustomEmailGrantTest::class, 'afterTokenStaticCallback']),
                \RuntimeException::class,
            ],
            'throw exception in callback from public method' => [
                $req,
                \Closure::fromCallable([$this, 'afterTokenCallback']),
                \InvalidArgumentException::class,
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::setEmailValidation
     * @covers ::respondToAccessTokenRequest
     * @covers ::getIdentifier
     * @dataProvider provideEmailValidationCallbacks
     */
    public function testEmailValidationCallback(
        ServerRequestInterface $req,
        ?\Closure $callback = null,
        ?string $exClass = null
    ): void {
        if (!is_null($exClass)) {
            $this->expectException($exClass);
        }

        $tokenTtl = new \DateInterval('PT15M');
        if (!is_null($callback)) {
            $this->grant->setEmailValidation($callback);
        }

        $response = $this->grant->respondToAccessTokenRequest($req, new MailSentResponse(), $tokenTtl);

        if (is_null($exClass)) {
            static::assertInstanceOf(MailSentResponse::class, $response);
        }
    }

    public function provideEmailValidationCallbacks(): array
    {
        $factory = new HttpFactory();
        $clientCredentials = \base64_encode('Aladdin:open sesame');
        $req = $factory->createServerRequest('POST', 'https://example.dev/token')
            ->withParsedBody([
                'grant' => 'custom_email',
                'email' => 'fake_blacklisted@example.dev',
            ])
            ->withHeader('Authorization', "Basic {$clientCredentials}")
            ->withHeader('Content-Type', 'application/json');

        return [
            'correct request' => [
                $req,
            ],
            'filter email' => [
                $req,
                static function ($str) {
                    return $str !== 'fake_blacklisted@example.dev';
                },
                OAuthServerException::class,
            ],
            'callback without return bool' => [
                $req,
                static function ($str) {
                    return 'foobar';
                },
                OAuthServerException::class,
            ],
            'filter email' => [
                $req,
                static function ($str) {
                    throw new \InvalidArgumentException('Wrong');
                },
                OAuthServerException::class,
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::getIdentifier
     */
    public function testGetIdentifier(): void
    {
        $repo = new FakeUserRepository();
        $grant = new CustomEmailGrant($repo, static function () {
        });
        static::assertSame('custom_email', $grant->getIdentifier());
    }

    public static function afterTokenStaticCallback(): void
    {
        throw new \RuntimeException('Exception after token issued');
    }

    public function afterTokenCallback(): void
    {
        throw new \InvalidArgumentException('Exception after token issued');
    }

    public function onAccessTokenIssued(string $email, AccessTokenEntityInterface $accessToken, ClientEntityInterface $client, UserEntityInterface $user, array $scopes): void
    {
        $this->email = $email;
        $this->accessToken = $accessToken;
        $this->client = $client;
        $this->user = $user;
        $this->scopes = $scopes;
    }
}
