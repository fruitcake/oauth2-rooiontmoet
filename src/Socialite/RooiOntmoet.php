<?php

namespace RooiOntmoet\OAuth2\Client\Socialite;

use Illuminate\Http\Request;
use Laravel\Socialite\Two\User;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use RooiOntmoet\OAuth2\Client\Provider\RooiOntmoet;

class RooiOntmoetProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The OAuth2 Client Provider
     *
     * @var RooiOntmoet
     */
    protected $provider;

    /**
     * Create a new provider instance.
     *
     * @param  Request  $request
     * @param  string  $clientId
     * @param  string  $clientSecret
     * @param  string  $redirectUrl
     * @return void
     */
    public function __construct(Request $request, $clientId, $clientSecret, $redirectUrl)
    {
        $this->provider = new RooiOntmoet();

        parent::__construct($request, $clientId, $clientSecret, $redirectUrl);
    }
    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->provider->getBaseAuthorizationUrl(), $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->provider->getBaseAccessTokenUrl([]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $url = $this->provider->baseResourceUrl . '/users';

        $request = $this->provider->getAuthenticatedRequest('GET', $url, $token);
        $response = $this->provider->getHttpClient()->send($request);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_add(
          parent::getTokenFields($code), 'grant_type', 'authorization_code'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
          'id'       => $user['id'],
          'name'     => $user['name'],
          'email'    => isset($user['email']) ? $user['email'] : null,
        ]);
    }

}
