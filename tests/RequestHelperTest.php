<?php

namespace Mosparo\ApiClient;

use PHPUnit\Framework\TestCase;

class RequestHelperTest extends TestCase
{
    protected string $publicKey = 'publicKey';
    protected string $privateKey = 'privateKey';

    public function testCreateHmacHash()
    {
        $requestHelper = new RequestHelper($this->publicKey, $this->privateKey);

        $data = 'testData';
        $hmacHash = hash_hmac('sha256', $data, $this->privateKey);

        $this->assertEquals($hmacHash, $requestHelper->createHmacHash($data));
    }

    public function testPrepareFormData()
    {
        $requestHelper = new RequestHelper($this->publicKey, $this->privateKey);

        $data = [
            'name' => 'Test Tester',
            'address' => [
                'street' => 'Teststreet',
                'number' => '123',
            ],
        ];

        $targetArray = [
            'address' => [
                'street' => hash('sha256', 'Teststreet'),
                'number' => hash('sha256', '123'),
            ],
            'name' => hash('sha256', 'Test Tester'),
        ];

        $this->assertEquals($targetArray, $requestHelper->prepareFormData($data));
    }

    public function testToJson()
    {
        $requestHelper = new RequestHelper($this->publicKey, $this->privateKey);

        $data = [
            'name' => 'Test Tester',
            'address' => [
                'street' => 'Teststreet',
                'number' => 123,
            ],
            'valid' => false,
            'data' => []
        ];

        $targetJson = '{"name":"Test Tester","address":{"street":"Teststreet","number":123},"valid":false,"data":{}}';

        $this->assertEquals($targetJson, $requestHelper->toJson($data));
    }

    public function testCreateFormDataHmacHash()
    {
        $requestHelper = new RequestHelper($this->publicKey, $this->privateKey);

        $data = [
            'name' => 'Test Tester',
            'address' => [
                'street' => 'Teststreet',
                'number' => 123,
            ],
            'valid' => false,
            'data' => []
        ];

        $targetJson = '{"name":"Test Tester","address":{"street":"Teststreet","number":123},"valid":false,"data":{}}';
        $targetValue = hash_hmac('sha256', $targetJson, $this->privateKey);

        $this->assertEquals($targetValue, $requestHelper->createFormDataHmacHash($data));
    }
}
