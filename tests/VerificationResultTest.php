<?php

namespace Mosparo\ApiClient;

use PHPUnit\Framework\TestCase;

class VerificationResultTest extends TestCase
{
    public function testVerificationResult()
    {
        $verifiedFields = [
            'name' => VerificationResult::FIELD_VALID,
            'street' => VerificationResult::FIELD_INVALID,
        ];
        $issues = [
            ['name' => 'street', 'message' => 'Missing in form data, verification not possible.']
        ];
        $verificationResult = new VerificationResult(false, true, $verifiedFields, $issues);

        $this->assertFalse($verificationResult->isSubmittable());
        $this->assertTrue($verificationResult->isValid());
        $this->assertEquals($verifiedFields, $verificationResult->getVerifiedFields());
        $this->assertEquals('valid', $verificationResult->getVerifiedField('name'));
        $this->assertEquals('invalid', $verificationResult->getVerifiedField('street'));
        $this->assertEquals('not-verified', $verificationResult->getVerifiedField('number'));
        $this->assertTrue($verificationResult->hasIssues());
        $this->assertEquals($issues, $verificationResult->getIssues());
    }
}
