<?php

use Matecat\SimpleS3\Components\Validators\S3ObjectSafeNameValidator;

class S3ObjectSafeNameValidatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function test_invalid_strings()
    {
        $invalidStrings = [
            '.name]',
            '..{name}',
        ];

        foreach ($invalidStrings as $invalidString) {
            $this->assertFalse(S3ObjectSafeNameValidator::isValid($invalidString));
        }
    }

    /**
     * @test
     */
    public function test_valid_strings()
    {
        $validStrings = [
            'valid object name',
            'valid/object/name',
            '1/2/3/valid-name',
            'valid/!name(1)',
            'valid/\'name(1)',
        ];

        foreach ($validStrings as $validString) {
            $this->assertTrue(S3ObjectSafeNameValidator::isValid($validString));
        }
    }
}
