<?php

/**
 * The mosparo PHP Client
 *
 * @author Matthias Zobrist <matthias.zobrist@zepi.net>
 * @copyright 2021-2022 mosparo
 * @license MIT
 */

namespace Mosparo\ApiClient;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use stdClass;

/**
 * The mosparo PHP client
 *
 * @author Matthias Zobrist <matthias.zobrist@zepi.net>
 */
class Client
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $publicKey;

    /**
     * @var string
     */
    protected $privateKey;

    /**
     * @var array
     */
    protected $clientArguments;

    /**
     * Constructs the object
     *
     * @param string $host Host of the mosparo installation
     * @param string $publicKey Public key of the mosparo project
     * @param string $privateKey Private key of the mosparo project
     * @param array $clientArguments Guzzle client arguments
     */
    public function __construct(string $host, string $publicKey, string $privateKey, array $clientArguments = [])
    {
        $this->host = $host;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->clientArguments = $clientArguments;
    }

    /**
     * Validates the given form data with the configured mosparo
     * instance. Returns true if the submission is valid or false if the
     * submission isn't valid.
     *
     * @param array $formData
     * @param string $submitToken
     * @param string $validationToken
     * @return bool
     *
     * @throws \Mosparo\ApiClient\Exception Submit or validation token not available.
     * @throws \Mosparo\ApiClient\Exception An error occurred while sending the request to mosparo.
     */
    public function validateSubmission(array $formData, string $submitToken = null, string $validationToken = null): bool
    {
        $requestHelper = new RequestHelper($this->publicKey, $this->privateKey);

        if ($submitToken === null && isset($formData['_mosparo_submitToken'])) {
            $submitToken = $formData['_mosparo_submitToken'];
        }

        if ($validationToken === null && isset($formData['_mosparo_validationToken'])) {
            $validationToken = $formData['_mosparo_validationToken'];
        }

        if ($submitToken === null || $validationToken === null) {
            throw new Exception('Submit or validation token not available.');
        }

        // Create the signatures
        $formData = $requestHelper->prepareFormData($formData);
        $formSignature = $requestHelper->createFormDataHmacHash($formData);

        $validationSignature = $requestHelper->createHmacHash($validationToken);
        $verificationSignature = $requestHelper->createHmacHash($validationSignature . $formSignature);

        // Prepare the request
        $apiEndpoint = '/api/v1/verification/verify';
        $requestData = [
            'submitToken' => $submitToken,
            'validationSignature' => $validationSignature,
            'formSignature' => $formSignature,
            'formData' => $formData,
        ];
        $requestSignature = $requestHelper->createHmacHash($apiEndpoint . $formSignature);

        $data = [
            'auth' => [$this->publicKey, $requestSignature],
            'headers' => [
                'Accept' => 'application/json'
            ],
            'json' => $requestData
        ];

        $result = $this->sendRequest($apiEndpoint, $data);
        if (isset($result->valid) && $result->valid && $result->verificationSignature === $verificationSignature) {
            return true;
        }

        return false;
    }

    /**
     * Sends the method to mosparo and returns the response.
     *
     * @param string $url
     * @param string $data
     * @return \StdClass
     *
     * @throws \Mosparo\ApiClient\Exception An error occurred while sending the request to mosparo.
     * @throws \Mosparo\ApiClient\Exception Response from API invalid.
     */
    protected function sendRequest(string $url, array $data): StdClass
    {
        $args = array_merge([
            'base_uri' => $this->host,
        ], $this->clientArguments);

        $client = new GuzzleClient($args);

        try {
            $response = $client->request('POST', $url, $data);
            $result = json_decode((string) $response->getBody());
        } catch (GuzzleException $e) {
            throw new Exception('An error occurred while sending the request to mosparo.', 0, $e);
        }

        if (!$result) {
            throw new Exception('Response from API invalid.');
        }

        return $result;
    }
}