<?php

/**
 * The mosparo PHP Client
 *
 * @author Matthias Zobrist <matthias.zobrist@zepi.net>
 * @copyright 2021-2025 mosparo
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
     * all needed information.
     *
     * @param array $formData
     * @param string $submitToken
     * @param string $validationToken
     * @return \Mosparo\ApiClient\VerificationResult
     *
     * @throws \Mosparo\ApiClient\Exception Submit or validation token not available.
     * @throws \Mosparo\ApiClient\Exception An error occurred while sending the request to mosparo.
     */
    public function verifySubmission(array $formData, string $submitToken = null, string $validationToken = null): VerificationResult
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

        $res = $this->sendRequest('POST', $apiEndpoint, $data);

        // Check if it is submittable
        $isSubmittable = false;
        $issues = $res['issues'] ?? [];
        $debugInformation = null;
        if (isset($res['valid']) && $res['valid'] && isset($res['verificationSignature']) && $res['verificationSignature'] === $verificationSignature) {
            $isSubmittable = true;
        } else if (isset($res['error']) && $res['error']) {
            $issues[] = [ 'message' => $res['errorMessage'] ];
            $debugInformation = $res['debugInformation'] ?? null;
        }

        return new VerificationResult(
            $isSubmittable,
            $res['valid'] ?? false,
            $res['verifiedFields'] ?? [],
            $issues,
            $debugInformation
        );
    }

    /**
     * DEPRECATED: Use Client::verifySubmission() instead.
     *
     * Validates the given form data with the configured mosparo
     * instance. Returns a VerificationResult object which contains
     * all needed information.
     *
     * @deprecated 1.0.2 This method has the wrong method name. Please use Client::verifySubmission() instead.
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
        return $this->verifySubmission($formData, $submitToken, $validationToken);
    }

    /**
     * Retrieves the statistics data from mosparo for the given time range,
     * counted by date.
     *
     * @param int $range Time range in seconds (will be rounded up to a full day since mosparo v1.1)
     * @param \DateTime $startDate The start date from which the statistics are to be returned (requires mosparo v1.1)
     * @return \Mosparo\ApiClient\StatisticResult
     *
     * @throws \Mosparo\ApiClient\Exception An error occurred while sending the request to mosparo.
     * @throws \Mosparo\ApiClient\Exception Response from API invalid.
     */
    public function getStatisticByDate(int $range = 0, \DateTime $startDate = null): StatisticResult
    {
        $requestHelper = new RequestHelper($this->publicKey, $this->privateKey);

        // Prepare the request
        $apiEndpoint = '/api/v1/statistic/by-date';
        $queryData = [];
        if ($range > 0) {
            $queryData['range'] = $range;
        }

        if ($startDate !== null) {
            $queryData['startDate'] = $startDate->format('Y-m-d');
        }

        $requestSignature = $requestHelper->createHmacHash($apiEndpoint . $requestHelper->toJson($queryData));

        $data = [
            'auth' => [$this->publicKey, $requestSignature],
            'headers' => [
                'Accept' => 'application/json'
            ],
            'query' => $queryData
        ];

        $res = $this->sendRequest('GET', $apiEndpoint, $data);

        if (isset($res['error'])) {
            throw new Exception(
                $res['errorMessage'] ?? 'An error occurred in the connection to mosparo.',
                0,
                null,
                $res['debugInformation'] ?? null
            );
        }

        return new StatisticResult(
            $res['data']['numberOfValidSubmissions'],
            $res['data']['numberOfSpamSubmissions'],
            $res['data']['numbersByDate']
        );
    }

    /**
     * Stores the rule package content via the mosparo API in the given rule package.
     *
     * @param int $rulePackageId
     * @param string $rulePackageContent
     * @param string $rulePackageHash
     * @return \Mosparo\ApiClient\RulePackageImportResult
     *
     * @throws \Mosparo\ApiClient\Exception An error occurred while sending the request to mosparo.
     */
    public function storeRulePackage(int $rulePackageId, string $rulePackageContent, string $rulePackageHash): RulePackageImportResult
    {
        $requestHelper = new RequestHelper($this->publicKey, $this->privateKey);

        // Prepare the request
        $apiEndpoint = '/api/v1/rule-package/import';
        $requestData = [
            'rulePackageId' => $rulePackageId,
            'rulePackageContent' => $rulePackageContent,
            'rulePackageHash' => $rulePackageHash,
        ];
        $requestSignature = $requestHelper->createHmacHash($apiEndpoint . $requestHelper->toJson($requestData));

        $data = [
            'auth' => [$this->publicKey, $requestSignature],
            'headers' => [
                'Accept' => 'application/json'
            ],
            'json' => $requestData
        ];

        $res = $this->sendRequest('POST', $apiEndpoint, $data);

        if (isset($res['error'])) {
            throw new Exception(
                $res['errorMessage'] ?? 'An error occurred in the connection to mosparo.',
                0,
                null,
                $res['debugInformation'] ?? null
            );
        }

        return new RulePackageImportResult(
            $res['successful'] ?? false,
            $res['verifiedHash'] ?? false
        );
    }

    /**
     * Sends the method to mosparo and returns the response.
     *
     * @param string $method
     * @param string $url
     * @param string $data
     * @return array
     *
     * @throws \Mosparo\ApiClient\Exception An error occurred while sending the request to mosparo.
     * @throws \Mosparo\ApiClient\Exception Response from API invalid.
     */
    protected function sendRequest(string $method, string $url, array $data): array
    {
        $args = array_merge([
            'base_uri' => $this->host,
        ], $this->clientArguments);

        $client = new GuzzleClient($args);

        try {
            $response = $client->request($method, $url, $data);
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