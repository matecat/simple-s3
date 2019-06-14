<?php

use SimpleS3\Components\Encoders\S3ObjectSafeNameEncoder;

class S3ObjectSafeNameEncoderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function test_encode_strings()
    {
        $unsafeString = 'folder/to/{unsafe}/[EN][] test_dummy.txt';
        $safeString = 'folder/to/3gTZd4unsafe5*JX(X/wL5HoaENOtdJXDwL5HoaOtdJXDUvPRTItest_dummy.txt';

        $this->assertEquals(S3ObjectSafeNameEncoder::encode($unsafeString), $safeString);

        $unsafeString = '文档/桌面/test_dummy.txt';
        $safeString = '文档/桌面/test_dummy.txt';

        $this->assertEquals(S3ObjectSafeNameEncoder::encode($unsafeString), $safeString);
    }

    /**
     * @test
     */
    public function test_decode_strings()
    {
        $unsafeString = 'folder/to/{unsafe}/[EN][] test_dummy.txt';
        $safeString = 'folder/to/3gTZd4unsafe5*JX(X/wL5HoaENOtdJXDwL5HoaOtdJXDUvPRTItest_dummy.txt';

        $this->assertEquals(S3ObjectSafeNameEncoder::decode($safeString), $unsafeString);

        $unsafeString = 'folder/to/<unsafe string>.txt';
        $safeString = 'folder/to/DGiKr*unsafeUvPRTIstringPLV7Ce.txt';

        $this->assertEquals(S3ObjectSafeNameEncoder::decode($safeString), $unsafeString);
    }


}
