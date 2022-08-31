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
    protected string $host;

    /**
     * @var string
     */
    protected string $publicKey;

    /**
     * @var string
     */
    protected string $privateKey;

    /**
     * @var array
     */
    protected array $clientArguments;

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
     * instance. Returns a VerificationResult object which contains
     * all needed informations.
     *
     * @param array $formData
     * @param string $submitToken
     * @param string $validationToken
     * @return \Mosparo\ApiClient\VerificationResult
     *
     * @throws \Mosparo\ApiClient\Exception Submit or validation token not available.
     * @throws \Mosparo\ApiClient\Exception An error occurred while sending the request to mosparo.
     */
    public function validateSubmission(array $formData, string $submitToken = null, string $validationToken = null): VerificationResult
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
        $requestSignature = $requestHelper->createHmacHash($apiEndpoint . $requestHelper->toJson($requestData));

        $data = [
            'auth' => [$this->publicKey, $requestSignature],
            'headers' => [
                'Accept' => 'application/json'
            ],
            'json' => $requestData
        ];

        $res = $this->sendRequest($apiEndpoint, $data);

        // Check if it is submittable
        $isSubmittable = false;
        $issues = $res['issues'] ?? [];
        if (isset($res['valid']) && $res['valid'] && isset($res['verificationSignature']) && $res['verificationSignature'] === $verificationSignature) {
            $isSubmittable = true;
        } else if (isset($res['error']) && $res['error']) {
            $issues[] = [ 'message' => $res['errorMessage'] ];
        }

        return new VerificationResult(
            $isSubmittable,
            $res['valid'] ?? false,
            $res['verifiedFields'] ?? [],
            $issues
        );
    }

    /**
     * Sends the method to mosparo and returns the response.
     *
     * @param string $url
     * @param string $data
     * @return array
     *
     * @throws \Mosparo\ApiClient\Exception An error occurred while sending the request to mosparo.
     * @throws \Mosparo\ApiClient\Exception Response from API invalid.
     */
    protected function sendRequest(string $url, array $data): array
    {
        $args = array_merge([
            'base_uri' => $this->host,
        ], $this->clientArguments);

        $client = new GuzzleClient($args);

        try {
            $response = $client->request('POST', $url, $data);
            $result = json_decode((string) $response->getBody(), true);
        } catch (GuzzleException $e) {
            throw new Exception('An error occurred while sending the request to mosparo.', 0, $e);
        }

        if (!$result) {
            throw new Exception('Response from API invalid.');
        }

        return $result;
    }
}