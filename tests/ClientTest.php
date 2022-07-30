<?php

namespace Mosparo\ApiClient;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    protected MockHandler $handler;

    protected HandlerStack $handlerStack;

    protected array $history = [];

    public function setUp(): void
    {
        $historyMiddleware = Middleware::history($this->history);

        $this->handler = new MockHandler();

        $this->handlerStack = HandlerStack::create($this->handler);
        $this->handlerStack->push($historyMiddleware);
    }

    public function testValidateSubmissionWithoutTokens()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Submit or validation token not available.');

        $apiClient = new Client('http://test.local', 'testPublicKey', 'testPrivateKey', ['handler' => $this->handlerStack]);
        $result = $apiClient->validateSubmission(['name' => 'John Example']);
    }

    public function testValidateSubmissionWithoutValidationTokens()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Submit or validation token not available.');

        $apiClient = new Client('http://test.local', 'testPublicKey', 'testPrivateKey', ['handler' => $this->handlerStack]);
        $result = $apiClient->validateSubmission(['name' => 'John Example', '_mosparo_submitToken' => 'submitToken']);
    }

    public function testValidateSubmissionFormTokensEmptyResponse()
    {
        $this->handler->append(new Response(200, ['Content-Type' => 'application/json'], json_encode([])));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Response from API invalid.');

        $apiClient = new Client('http://test.local', 'testPublicKey', 'testPrivateKey', ['handler' => $this->handlerStack]);
        $result = $apiClient->validateSubmission(['name' => 'John Example', '_mosparo_submitToken' => 'submitToken', '_mosparo_validationToken' => 'validationToken']);
    }

    public function testValidateSubmissionTokensAsArgumentEmptyResponse()
    {
        $this->handler->append(new Response(200, ['Content-Type' => 'application/json'], json_encode([])));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Response from API invalid.');

        $apiClient = new Client('http://test.local', 'testPublicKey', 'testPrivateKey', ['handler' => $this->handlerStack]);
        $result = $apiClient->validateSubmission(['name' => 'John Example'], 'submitToken', 'validationToken');
    }

    public function testValidateSubmissionConnectionError()
    {
        $this->handler->append(new RequestException('Error Communicating with Server', new Request('GET', 'test')));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('An error occurred while sending the request to mosparo.');

        $apiClient = new Client('http://test.local', 'testPublicKey', 'testPrivateKey', ['handler' => $this->handlerStack]);
        $result = $apiClient->validateSubmission(['name' => 'John Example'], 'submitToken', 'validationToken');
    }

    public function testValidateSubmissionIsValid()
    {
        $privateKey = 'testPrivateKey';
        $submitToken = 'submitToken';
        $validationToken = 'validationToken';
        $formData = ['name' => 'John Example'];

        // Prepare the test data
        $preparedFormData = $this->cleanupFormData($formData);
        $payload = http_build_query($preparedFormData) . $submitToken;

        $validationSignature = $this->createHmacHash($validationToken, $privateKey);
        $formSignature = $this->createHmacHash($payload, $privateKey);
        $verificationSignature = $this->createHmacHash($validationSignature . $formSignature, $privateKey);

        // Set the response
        $this->handler->append(new Response(200, ['Content-Type' => 'application/json'], json_encode(['valid' => true, 'verificationSignature' => $verificationSignature])));

        // Start the test
        $apiClient = new Client('http://test.local', 'testPublicKey', $privateKey, ['handler' => $this->handlerStack]);

        $result = $apiClient->validateSubmission($formData, $submitToken, $validationToken);

        // Check the result
        $this->assertTrue($result);
        $this->assertEquals(count($this->history), 1);

        parse_str((string) $this->history[0]['request']->getBody(), $requestData);

        $this->assertEquals($requestData['publicKey'], 'testPublicKey');
        $this->assertEquals($requestData['submitToken'], 'submitToken');
        $this->assertEquals($requestData['validationSignature'], $validationSignature);
        $this->assertEquals($requestData['formSignature'], $formSignature);
    }

    public function testValidateSubmissionIsNotValid()
    {
        $privateKey = 'testPrivateKey';
        $submitToken = 'submitToken';
        $validationToken = 'validationToken';
        $formData = ['name' => 'John Example'];

        // Prepare the test data
        $preparedFormData = $this->cleanupFormData($formData);
        $payload = http_build_query($preparedFormData) . $submitToken;

        $validationSignature = $this->createHmacHash($validationToken, $privateKey);
        $formSignature = $this->createHmacHash($payload, $privateKey);
        $verificationSignature = $this->createHmacHash($validationSignature . $formSignature, $privateKey);

        // Set the response
        $this->handler->append(new Response(200, ['Content-Type' => 'application/json'], json_encode(['error' => true, 'errorMessage' => 'Validation failed.'])));

        // Start the test
        $apiClient = new Client('http://test.local', 'testPublicKey', $privateKey, ['handler' => $this->handlerStack]);

        $result = $apiClient->validateSubmission($formData, $submitToken, $validationToken);

        // Check the result
        $this->assertFalse($result);
        $this->assertEquals(count($this->history), 1);

        parse_str((string) $this->history[0]['request']->getBody(), $requestData);

        $this->assertEquals($requestData['publicKey'], 'testPublicKey');
        $this->assertEquals($requestData['submitToken'], 'submitToken');
        $this->assertEquals($requestData['validationSignature'], $validationSignature);
        $this->assertEquals($requestData['formSignature'], $formSignature);
    }


    /**
     * Internal methods to simulate the client functionality
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

    protected function createHmacHash(string $data, string $privateKey): string
    {
        return hash_hmac('sha256', $data, $privateKey);
    }
}
