<?php

declare(strict_types=1);

namespace Ybelenko\OAuth2\Server\ResponseTypes;

use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use Ybelenko\OAuth2\Server\Entities\FakeAccessTokenEntity;

/**
 * @coversDefaultClass \Ybelenko\OAuth2\Server\ResponseTypes\MailSentResponse
 *
 * @internal
 *
 * @small
 */
final class MailSentResponseTest extends TestCase
{
    /**
     * @covers ::generateHttpResponse
     */
    public function testGenerateHttpResponse(): void
    {
        $factory = new HttpFactory();
        $response = new MailSentResponse();
        $token = new FakeAccessTokenEntity();
        $expirationTime = (new \DateTimeImmutable())->modify('+86400 second');
        $token->setExpiryDateTime($expirationTime);
        $response->setAccessToken($token);
        $httpResponse = $response->generateHttpResponse($factory->createResponse());

        static::assertSame(200, $httpResponse->getStatusCode());
        static::assertJsonStringEqualsJsonString(
            json_encode([
                'expires_in' => 86400,
                'message' => 'Mail with recover link has been sent to provided address',
            ]),
            (string)$httpResponse->getBody()
        );
    }
}
