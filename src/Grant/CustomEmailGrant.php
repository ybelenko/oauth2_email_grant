<?php

declare(strict_types=1);
/**
 * OAuth 2.0 Email Grant(Custom).
 * PHP version 8.0
 *
 * @package ybelenko/oauth2_email_grant
 * @author  Yuriy Belenko <yura-bely@mail.ru>
 * @link    https://github.com/ybelenko/oauth2_email_grant
 */

namespace Ybelenko\OAuth2\Server\Grant;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ybelenko\OAuth2\Server\ResponseTypes\MailSentResponse;

/**
 * Custom user email grant class.
 *
 * This custom grant developed to issue access tokens based on user email.
 * Comparing to traditional way server response doesn't contain access token.
 * User calls access token endpoint with email credential then authorization server
 * generates new access token and sends it to provided email.
 *
 * Original use case is password reset email.
 * This grant is tiny modification of \League\OAuth2\Server\Grant\PasswordGrant
 */
class CustomEmailGrant extends AbstractGrant
{
    protected \Closure $onAccessTokenIssued;
    protected ?\Closure $emailValidation;

    /**
     * @param UserRepositoryInterface $userRepository
     * @param Closure                 $onAccessTokenIssued
     * @param Closure|null            $emailValidation
     */
    public function __construct(
        UserRepositoryInterface $userRepository,
        \Closure $onAccessTokenIssued,
        ?\Closure $emailValidation = null
    ) {
        $this->setUserRepository($userRepository);
        $this->onAccessTokenIssued = $onAccessTokenIssued;
        $this->emailValidation = $emailValidation ?? static function ($str) {
            return is_string($str) && !empty($str);
        };
    }

    /**
     * @return void
     */
    public function setOnAccessTokenIssued(\Closure $callback)
    {
        $this->onAccessTokenIssued = $callback;
    }

    /**
     * @return void
     */
    public function setEmailValidation(?\Closure $callback = null)
    {
        $this->emailValidation = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        \DateInterval $accessTokenTTL
    ) {
        // Validate request
        $client = $this->validateClient($request);
        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request, $this->defaultScope));
        $user = $this->validateUser($request, $client);

        // Finalize the requested scopes
        $finalizedScopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client, $user->getIdentifier());

        // Issue and persist new access token
        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $user->getIdentifier(), $finalizedScopes);
        $this->getEmitter()->emit(new RequestAccessTokenEvent(RequestEvent::ACCESS_TOKEN_ISSUED, $request, $accessToken));
        $responseType->setAccessToken($accessToken);

        $this->onAccessTokenIssued->__invoke(
            $this->getRequestParameter('email', $request),
            $accessToken,
            $client,
            $user,
            $finalizedScopes
        );

        // grant supports only specific response
        // overwrite it silently
        if ($responseType instanceof MailSentResponse === false) {
            $responseType = new MailSentResponse();
        }

        return $responseType;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    protected function validateUser(ServerRequestInterface $request, ClientEntityInterface $client)
    {
        $email = $this->getRequestParameter('email', $request);

        // validate email the way you like
        $emailValid = is_string($email) && !empty($email);

        // perform extra custom validation which sometimes throws exceptions
        if ($this->emailValidation instanceof \Closure) {
            try {
                $emailValid = $this->emailValidation->__invoke($email);
            } catch (\Throwable $e) {
                $emailValid = false;
            }
        }

        if ($emailValid !== true) {
            throw OAuthServerException::invalidRequest('email');
        }

        $user = $this->userRepository->getUserEntityByUserCredentials(
            $email,
            '',
            $this->getIdentifier(),
            $client
        );

        if ($user instanceof UserEntityInterface === false) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidCredentials();
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'custom_email';
    }
}
