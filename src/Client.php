<?php

declare(strict_types=1);

namespace Boekuwzending;

use Boekuwzending\Endpoints\LabelEndpoint;
use Boekuwzending\Endpoints\MeEndpoint;
use Boekuwzending\Endpoints\ShipmentEndpoint;
use Boekuwzending\Endpoints\TrackingEndpoint;
use Boekuwzending\Exception\AuthorizationFailedException;
use Boekuwzending\Exception\NoCredentialsException;
use Boekuwzending\Exception\RequestFailedException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class Client.
 */
class Client
{
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';

    public const ENVIRONMENT_LIVE = 'live';
    public const ENVIRONMENT_STAGING = 'staging';

    public const URL_LIVE = 'https://api.mijn.boekuwzending.com';
    public const URL_STAGING = 'https://api.staging.mijn.boekuwzending.com';

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var MeEndpoint
     */
    public $me;

    /**
     * @var ShipmentEndpoint
     */
    public $shipment;

    /**
     * @var TrackingEndpoint
     */
    public $tracking;

    /**
     * @var LabelEndpoint
     */
    public $label;

    /**
     * BuzApiClient constructor.
     *
     * @param HttpClientInterface $httpClient
     */
    public function __construct(HttpClientInterface $httpClient = null)
    {
        $this->httpClient = $httpClient;

        $this->registerEndpoints();
    }

    /**
     * @param string $clientId
     * @param string $clientSecret
     */
    public function setCredentials(string $clientId, string $clientSecret): void
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    /**
     * @param string     $url
     * @param string     $method
     * @param array|null $body
     *
     * @return array
     * @throws AuthorizationFailedException
     * @throws RequestFailedException
     */
    public function request(string $url, string $method, array $body = null): array
    {
        if (empty($this->accessToken)) {
            $this->authorize();
        }

        try {
            $response = $this->httpClient->request($method, $url, [
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $this->accessToken),
                ],
                'json' => $body ?? [],
            ]);
        } catch (TransportExceptionInterface $e) {
            throw new RequestFailedException($e->getMessage());
        }

        try {
            return json_decode($response->getContent(), true);
        } catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $e) {
            throw new RequestFailedException($e->getMessage());
        }
    }

    private function registerEndpoints(): void
    {
        $this->me = new MeEndpoint($this);
        $this->shipment = new ShipmentEndpoint($this);
        $this->tracking = new TrackingEndpoint($this);
        $this->label = new LabelEndpoint($this);
    }

    /**
     * @throws AuthorizationFailedException
     */
    private function authorize(): void
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new NoCredentialsException('API credentials not specified. Use Client::setCredentials');
        }

        try {
            $response = $this->httpClient->request('POST', '/token', [
                'body' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
            ]);
        } catch (TransportExceptionInterface $e) {
            throw new AuthorizationFailedException();
        }

        try {
            $response = json_decode($response->getContent(), true);
        } catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $e) {
            throw new AuthorizationFailedException();
        }

        $this->accessToken = $response['access_token'];
    }
}