<?php

namespace Mosparo\ApiClient;

use GuzzleHttp\Client as GuzzleClient;

class Client
{
    protected $host;

    protected $publicKey;

    protected $privateKey;

    protected $clientArguments;

    public function __construct($host, $publicKey, $privateKey, $clientArguments = [])
    {
        $this->host = $host;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->clientArguments = $clientArguments;
    }

    public function validateSubmission($formData, $submitToken = null, $validationToken = null)
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

    protected function sendRequest($url, $data)
    {
        $args = array_merge([
            'base_uri' => $this->host,
        ], $this->clientArguments);

        $client = new GuzzleClient($args);

        $response = $client->request('POST', $url, $data);
        $result = json_decode($response->getBody()->getContents());

        if ($result === false) {
            throw new Exception('Response from API invalid.');
        }

        return $result;
    }

    protected function cleanupFormData($formData): array
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

    protected function createHmacHash($data): string
    {
        return hash_hmac('sha256', $data, $this->privateKey);
    }
}