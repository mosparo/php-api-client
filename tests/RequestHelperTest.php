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

        $this->assertEquals('0646b5f2e09db205a8b3eb0e7429645561a1b9fdff1fcdb1fed9cd585108d850', $requestHelper->createHmacHash($data));
    }

    public function testPrepareFormData()
    {
        $requestHelper = new RequestHelper($this->publicKey, $this->privateKey);

        $data = [
            'name' => 'Test Tester',
            'address' => [
                'street' => 'Teststreet',
                'number' => 123,
            ],
            'email[]' => [
                'test@example.com'
            ],
            'data' => [],
            'website' => null,
        ];

        $targetArray = [
            'address' => [
                'street' => 'cc0bdb0377d3ba87046028784e8a4319972a7c9df31c645e80e14e8dd8735b6b',
                'number' => 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3',
            ],
            'name' => '153590093b8c278bb7e1fef026d8a59b9ba02701d1e0a66beac0938476f2a812',
            'email' => [
                '973dfe463ec85785f5f95af5ba3906eedb2d931c24e69824a89ea65dba4e813b'
            ],
            'data' => [],
            'website' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
        ];

        $this->assertEquals($targetArray, $requestHelper->prepareFormData($data));
    }

    public function testCleanupFormData()
    {
        $requestHelper = new RequestHelper($this->publicKey, $this->privateKey);

        $data = [
            '_mosparo_submitToken' => 'submitToken',
            '_mosparo_validationToken' => 'validationToken',
            'name' => 'Test Tester',
            'address' => [
                'street' => "Teststreet\r\nTest\r\nStreet",
                'number' => 123,
            ],
            'valid' => false,
            'email[]' => [
                'test@example.com'
            ],
            'data' => [],
            'website' => null,
        ];

        $expectedData = [
            'address' => [
                'number' => 123,
                'street' => "Teststreet\nTest\nStreet"
            ],
            'data' => [],
            'email' => [
                'test@example.com',
            ],
            'name' => 'Test Tester',
            'valid' => false,
            'website' => '',
        ];

        $this->assertEquals($expectedData, $requestHelper->cleanupFormData($data));
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
            'data' => [],
            'email' => null,
        ];

        $targetJson = '{"name":"Test Tester","address":{"street":"Teststreet","number":123},"valid":false,"data":{},"email":null}';

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
            'data' => [],
            'email' => null,
        ];

        $this->assertEquals('fb1ae1d7aa9a506fdebc1641cd466f3b0fdefa62c835a43c8001a24913f8b735', $requestHelper->createFormDataHmacHash($data));
    }
}
