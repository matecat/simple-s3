<?php

use SimpleS3\Components\Validators\S3BucketNameValidator;

class S3BucketNameValidatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function test_invalid_strings()
    {
        $invalidStrings = [
            'a', // too few words
            'ddddsadsadsadsadsadjsadjsaodjsaidsaojdsiaojdsioajdsiajdiosajdsiajdisoajdsiajdsiaojdsiaojdsaojdioejiojidoajdiaojiosjaiodjsaiodjsaiodjsaojdsiaojdisaojisdaosdja', // too much words
            'Not-a-valid-string', // string with capital letters
            'not-a-valid-string-', // string with a final dash
            'not-a-valid-string..', // string with consecutive periods
            '172.0.0.1', // ip address
            'not_a_valid_string', // underscores
            'my.-bucket', // dash before period
            'my-.bucket.com', // dash after period
            'not a valid name', // string with spaces
        ];

        foreach ($invalidStrings as $invalidString) {
            $this->assertFalse(S3BucketNameValidator::isValid($invalidString));
        }
    }


    /**
     * @test
     */
    public function test_valid_strings()
    {
        $validStrings = [
            'mauro-valid-test',
            'mauro-valid-test2',
            'mauro-valid-test3',
        ];

        foreach ($validStrings as $validString) {
            $this->assertTrue(S3BucketNameValidator::isValid($validString));
        }
    }
}
