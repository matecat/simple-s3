<?php

use SimpleS3\Helper\S3BucketNameValidator;

class S3BucketNameValidatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \SimpleS3\Exceptions\InvalidS3BucketNameException
     * @test
     */
    public function test_an_invalid_string_with_too_few_words()
    {
        S3BucketNameValidator::generateFromString('a');
    }

    /**
     * @expectedException \SimpleS3\Exceptions\InvalidS3BucketNameException
     * @test
     */
    public function test_an_invalid_string_with_too_much_words()
    {
        S3BucketNameValidator::generateFromString('ddddsadsadsadsadsadjsadjsaodjsaidsaojdsiaojdsioajdsiajdiosajdsiajdisoajdsiajdsiaojdsiaojdsaojdioejiojidoajdiaojiosjaiodjsaiodjsaiodjsaojdsiaojdisaojisdaosdja');
    }

    /**
     * @expectedException \SimpleS3\Exceptions\InvalidS3BucketNameException
     * @test
     */
    public function test_an_invalid_string_with_capital_letters()
    {
        S3BucketNameValidator::generateFromString('Not-a-valid-string');
    }

    /**
     * @expectedException \SimpleS3\Exceptions\InvalidS3BucketNameException
     * @test
     */
    public function test_an_invalid_string_with_a_final_dash()
    {
        S3BucketNameValidator::generateFromString('not-a-valid-string-');
    }

    /**
     * @expectedException \SimpleS3\Exceptions\InvalidS3BucketNameException
     * @test
     */
    public function test_an_invalid_string_with_consecutive_periods()
    {
        S3BucketNameValidator::generateFromString('not-a-valid-string..');
    }

    /**
     * @expectedException \SimpleS3\Exceptions\InvalidS3BucketNameException
     * @test
     */
    public function test_an_invalid_string_with_ip_address()
    {
        S3BucketNameValidator::generateFromString('172.0.0.1');
    }

    /**
     * @expectedException \SimpleS3\Exceptions\InvalidS3BucketNameException
     * @test
     */
    public function test_an_invalid_string_with_underscores()
    {
        S3BucketNameValidator::generateFromString('not_a_valid_string');
    }

    /**
     * @expectedException \SimpleS3\Exceptions\InvalidS3BucketNameException
     * @test
     */
    public function test_an_invalid_string_with_dash_before_period()
    {
        S3BucketNameValidator::generateFromString('my.-bucket');
    }

    /**
     * @expectedException \SimpleS3\Exceptions\InvalidS3BucketNameException
     * @test
     */
    public function test_an_invalid_string_with_dash_after_period()
    {
        S3BucketNameValidator::generateFromString('my-.bucket.com');
    }

    /**
     * @expectedException \SimpleS3\Exceptions\InvalidS3BucketNameException
     * @test
     */
    public function test_an_invalid_string_with_spaces()
    {
        S3BucketNameValidator::generateFromString('not a valid name');
    }

    /**
     * @test
     */
    public function test_valid_strings()
    {
        $this->assertEquals('mauro-valid-test', S3BucketNameValidator::generateFromString('mauro-valid-test'));
        $this->assertEquals('mauro-valid-test2', S3BucketNameValidator::generateFromString('mauro-valid-test2'));
        $this->assertEquals('mauro-valid-test3', S3BucketNameValidator::generateFromString('mauro-valid-test3'));
    }
}
