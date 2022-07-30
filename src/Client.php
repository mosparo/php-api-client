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
        if ($submitToken === null && isset($formData['_mosparo_submitToken'])) {
            $submitToken = $formData['_mosparo_submitToken'];
        }

        if ($validationToken === null && isset($formData['_mosparo_validationToken'])) {
            $validationToken = $formData['_mosparo_validationToken'];
        }

        if ($submitToken === null || $validationToken === null) {
            throw new Exception('Submit or validation token not available.');
        }

        $formData = $this->cleanupFormData($formData);
        $payload = http_build_query($formData) . $submitToken;

        $validationSignature = $this->createHmacHash($validationToken);
        $formSignature = $this->createHmacHash($payload);
        $verificationSignature = $this->createHmacHash($validationSignature . $formSignature);

        $data = [
            'form_params' => [
                'publicKey' => $this->publicKey,
                'submitToken' => $submitToken,
                'validationSignature' => $validationSignature,
                'formSignature' => $formSignature,
            ]
        ];
        $result = $this->sendRequest('/api/v1/verification/verify', $data);

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

    /**
     * Cleanups the given form data. The method removes possible
     * mosparo fields from the data, converts all line breaks and converts
     * the field keys to lower case characters.
     *
     * @param array $formData
     * @return array
     */
    protected function cleanupFormData(array $formData): array
    {
        if (isset($formData['_mosparo_submitToken'])) {
            unset($formData['_mosparo_submitToken']);
        }

        if (isset($formData['_mosparo_validationToken'])) {
            unset($formData['_mosparo_validationToken']);
        }

        foreach ($formData as $key => $value) {
            $formData[$key] = str_replace("\r\n", "\n", $value);
        }

        $formData = array_change_key_case($formData, CASE_LOWER);
        ksort($formData);

        return $formData;
    }

    /**
     * Creates the HMAC hash for the given string
     *
     * @param string $data
     * @return string
     */
    protected function createHmacHash(string $data): string
    {
        return hash_hmac('sha256', $data, $this->privateKey);
    }
}