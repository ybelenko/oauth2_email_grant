<?php

declare(strict_types=1);
/**
 * OAuth2 Email Grant(Custom)
 * PHP version 8.0
 *
 * @package ybelenko/oauth2_email_grant
 * @author  Yuriy Belenko <yura-bely@mail.ru>
 * @link    https://github.com/ybelenko/oauth2_email_grant
 */

namespace Ybelenko\OAuth2\Server\ResponseTypes;

use League\OAuth2\Server\ResponseTypes\AbstractResponseType;
use Psr\Http\Message\ResponseInterface;

/**
 * Important!!!
 * If you consider to extend this class don't add access token
 * to http response body, because it becomes huge security hole.
 */
class MailSentResponse extends AbstractResponseType
{
    /**
     * {@inheritdoc}
     */
    public function generateHttpResponse(ResponseInterface $response)
    {
        $expireDateTime = $this->accessToken->getExpiryDateTime()->getTimestamp();

        // important! don't ever write access or refresh token into properties below
        $responseParams = [
            'message' => 'Mail with recover link has been sent to provided address',
            'expires_in'   => $expireDateTime - \time(),
        ];

        $responseParams = \json_encode($responseParams);

        // @codeCoverageIgnoreStart
        if ($responseParams === false) {
            throw new \LogicException('Error encountered JSON encoding response parameters');
        }
        // @codeCoverageIgnoreEnd

        $response = $response
            ->withStatus(200)
            ->withHeader('pragma', 'no-cache')
            ->withHeader('cache-control', 'no-store')
            ->withHeader('content-type', 'application/json; charset=UTF-8');

        $response->getBody()->write($responseParams);

        return $response;
    }
}
