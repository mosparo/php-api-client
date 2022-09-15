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
        $publicKey = 'testPublicKey';
        $privateKey = 'testPrivateKey';
        $submitToken = 'submitToken';
        $validationToken = 'validationToken';
        $formData = ['name' => 'John Example'];

        // Prepare the test data
        $requestHelper = new RequestHelper($publicKey, $privateKey);

        $preparedFormData = $requestHelper->prepareFormData($formData);
        $formSignature = $requestHelper->createFormDataHmacHash($preparedFormData);

        $validationSignature = $requestHelper->createHmacHash($validationToken);
        $verificationSignature = $requestHelper->createHmacHash($validationSignature . $formSignature);

        // Set the response
        $this->handler->append(new Response(200, ['Content-Type' => 'application/json'], json_encode(['valid' => true, 'verificationSignature' => $verificationSignature])));

        // Start the test
        $apiClient = new Client('http://test.local', $publicKey, $privateKey, ['handler' => $this->handlerStack]);

        $result = $apiClient->validateSubmission($formData, $submitToken, $validationToken);

        // Check the result
        $this->assertInstanceOf(VerificationResult::class, $result);
        $this->assertEquals(count($this->history), 1);
        $this->assertTrue($result->isSubmittable());

        $requestData = json_decode((string) $this->history[0]['request']->getBody(), true);

        $this->assertEquals($requestData['formData'], $preparedFormData);
        $this->assertEquals($requestData['submitToken'], 'submitToken');
        $this->assertEquals($requestData['validationSignature'], $validationSignature);
        $this->assertEquals($requestData['formSignature'], $formSignature);
    }

    public function testValidateSubmissionIsNotValid()
    {
        $publicKey = 'testPublicKey';
        $privateKey = 'testPrivateKey';
        $submitToken = 'submitToken';
        $validationToken = 'validationToken';
        $formData = ['name' => 'John Example'];

        // Prepare the test data
        $requestHelper = new RequestHelper($publicKey, $privateKey);

        $preparedFormData = $requestHelper->prepareFormData($formData);
        $formSignature = $requestHelper->createFormDataHmacHash($preparedFormData);

        $validationSignature = $requestHelper->createHmacHash($validationToken);

        // Set the response
        $this->handler->append(new Response(200, ['Content-Type' => 'application/json'], json_encode(['error' => true, 'errorMessage' => 'Validation failed.'])));

        // Start the test
        $apiClient = new Client('http://test.local', 'testPublicKey', $privateKey, ['handler' => $this->handlerStack]);

        $result = $apiClient->validateSubmission($formData, $submitToken, $validationToken);

        // Check the result
        $this->assertInstanceOf(VerificationResult::class, $result);
        $this->assertEquals(count($this->history), 1);
        $this->assertFalse($result->isSubmittable());

        $requestData = json_decode((string) $this->history[0]['request']->getBody(), true);

        $this->assertEquals($requestData['formData'], $preparedFormData);
        $this->assertEquals($requestData['submitToken'], 'submitToken');
        $this->assertEquals($requestData['validationSignature'], $validationSignature);
        $this->assertEquals($requestData['formSignature'], $formSignature);
    }

    public function testGetStatisticByDateWithoutRange()
    {
        $publicKey = 'testPublicKey';
        $privateKey = 'testPrivateKey';
        $numbersByDate = [
            '2021-04-29' => [
                'numberOfValidSubmissions' => 0,
                'numberOfSpamSubmissions' => 10
            ]
        ];

        // Set the response
        $this->handler->append(new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'result' => true,
            'data' => [
                'numberOfValidSubmissions' => 0,
                'numberOfSpamSubmissions' => 10,
                'numbersByDate' => $numbersByDate
            ]
        ])));

        // Start the test
        $apiClient = new Client('http://test.local', $publicKey, $privateKey, ['handler' => $this->handlerStack]);

        $result = $apiClient->getStatisticByDate();

        // Check the result
        $this->assertInstanceOf(StatisticResult::class, $result);
        $this->assertEquals(count($this->history), 1);

        $this->assertEquals(0, $result->getNumberOfValidSubmissions());
        $this->assertEquals(10, $result->getNumberOfSpamSubmissions());
        $this->assertEquals($numbersByDate, $result->getNumbersByDate());
    }

    public function testGetStatisticByDateWithRange()
    {
        $publicKey = 'testPublicKey';
        $privateKey = 'testPrivateKey';
        $numbersByDate = [
            '2021-04-29' => [
                'numberOfValidSubmissions' => 2,
                'numberOfSpamSubmissions' => 5
            ]
        ];

        // Set the response
        $this->handler->append(new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'result' => true,
            'data' => [
                'numberOfValidSubmissions' => 2,
                'numberOfSpamSubmissions' => 5,
                'numbersByDate' => $numbersByDate
            ]
        ])));

        // Start the test
        $apiClient = new Client('http://test.local', $publicKey, $privateKey, ['handler' => $this->handlerStack]);

        $result = $apiClient->getStatisticByDate(3600);

        // Check the result
        $this->assertInstanceOf(StatisticResult::class, $result);
        $this->assertEquals(count($this->history), 1);
        $this->assertEquals('range=3600', $this->history[0]['request']->getUri()->getQuery());

        $this->assertEquals(2, $result->getNumberOfValidSubmissions());
        $this->assertEquals(5, $result->getNumberOfSpamSubmissions());
        $this->assertEquals($numbersByDate, $result->getNumbersByDate());
    }

    public function testGetStatisticByDateReturnsError()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Request not valid');

        $publicKey = 'testPublicKey';
        $privateKey = 'testPrivateKey';

        // Set the response
        $this->handler->append(new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'error' => true,
            'errorMessage' => 'Request not valid'
        ])));

        // Start the test
        $apiClient = new Client('http://test.local', $publicKey, $privateKey, ['handler' => $this->handlerStack]);

        $result = $apiClient->getStatisticByDate();
    }
}
