<?php

namespace App\Socialite;

use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SocialiteProvider
{
    /**
     * The provider google or facebook.
     *
     * @var string
     */
    protected $provider;

    /**
     * The HTTP Client instance.
     *
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * The client ID.
     *
     * @var string
     */
    protected $clientId;

    /**
     * The client secret.
     *
     * @var string
     */
    protected $clientSecret;

    /**
     * The redirect URL.
     *
     * @var string
     */
    protected $redirectUrl;

    /**
     * The custom Guzzle configuration options.
     *
     * @var array
     */
    protected $guzzle = [];

    /**
     * The base Facebook Graph URL.
     *
     * @var string
     */
    protected $graphUrl = 'https://graph.facebook.com';

    /**
     * The Graph API version for the request.
     *
     * @var string
     */
    protected $version = 'v3.3';


    /**
     * Create a new provider instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $clientId
     * @param  string  $clientSecret
     * @param  string  $redirectUrl
     * @param  array  $guzzle
     * @return void
     */
    public function __construct($provider='', $guzzle = [])
    {
        $this->guzzle = $guzzle;
        $this->clientId = config('services.'.$provider.'.client_id');
        $this->redirectUrl = config('services.'.$provider.'.redirect');
        $this->clientSecret = config('services.'.$provider.'.client_secret');
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        if($this->provider == 'google'){
        	return 'https://www.googleapis.com/oauth2/v4/token';
        } elseif ($this->provider == 'facebook') {
        	return $this->graphUrl.'/'.$this->version.'/oauth/access_token';
        } else {
        	return null;
        }    
    }

	/**
     * Get the access token response for the given refresh token.
     *
     * @param  string  $code
     * @return array
     */
    public function getAccessTokenResponseByRefresh($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            'form_params' => $this->getTokenByRefreshFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get the POST fields for the access token request by refresh token.
     *
     * @param  string  $code
     * @return array
     */
    protected function getTokenByRefreshFields($code)
    {
        return [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $code,
            'redirect_uri' => $this->redirectUrl,
        ];
    }

    /**
     * Get a instance of the Guzzle HTTP client.
     *
     * @return \GuzzleHttp\Client
     */
    protected function getHttpClient()
    {
        if (is_null($this->httpClient)) {
            $this->httpClient = new Client($this->guzzle);
        }

        return $this->httpClient;
    }

     /**
     * Set the Guzzle HTTP client instance.
     *
     * @param  \GuzzleHttp\Client  $client
     * @return $this
     */
    public function setHttpClient(Client $client)
    {
        $this->httpClient = $client;

        return $this;
    }
}