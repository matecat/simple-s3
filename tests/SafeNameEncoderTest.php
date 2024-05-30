<?php
namespace Matecat\SimpleS3\Tests;

use Matecat\SimpleS3\Components\Encoders\SafeNameEncoderInterface;
use Matecat\SimpleS3\Components\Encoders\UrlEncoder;

class SafeNameEncoderTest extends BaseTest
{
    /**
     * @var SafeNameEncoderInterface
     */
    private $encoder;

    protected function setUp()
    {
        parent::setUp();

        $this->encoder = new UrlEncoder();
    }

    /**
     * @test
     */
    public function test_encode_strings()
    {
        $unsafeString = 'cache-package/11/f6/4998301c0e4b9419452daec56efd727936b9__it-it/orig/os8.odt';
        $safeString = 'cache-package/11/f6/4998301c0e4b9419452daec56efd727936b9__it-it/orig/os8.odt';

        $this->assertEquals($this->encoder->encode($unsafeString), $safeString);

        $unsafeString = 'folder/to/{unsafe}/[EN][] test_dummy.txt';
        $safeString = 'folder/to/%7Bunsafe%7D/%5BEN%5D%5B%5D+test_dummy.txt';

        $this->assertEquals($this->encoder->encode($unsafeString), $safeString);

        $unsafeString = '文档/桌面/test_dummy.txt';
        $safeString = '%E6%96%87%E6%A1%A3/%E6%A1%8C%E9%9D%A2/test_dummy.txt';

        $this->assertEquals($this->encoder->encode($unsafeString), $safeString);
    }

    /**
     * @test
     */
    public function test_decode_strings()
    {
        $unsafeString = 'cache-package/11/f6/4998301c0e4b9419452daec56efd727936b9__it-it/orig/os8.odt';
        $safeString = 'cache-package/11/f6/4998301c0e4b9419452daec56efd727936b9__it-it/orig/os8.odt';

        $this->assertEquals($this->encoder->decode($safeString), $unsafeString);

        $unsafeString = 'folder/to/{unsafe}/[EN][] test_dummy.txt';
        $safeString = 'folder/to/%7Bunsafe%7D/%5BEN%5D%5B%5D+test_dummy.txt';

        $this->assertEquals($this->encoder->decode($safeString), $unsafeString);

        $unsafeString = '文档/桌面/test_dummy.txt';
        $safeString = '%E6%96%87%E6%A1%A3/%E6%A1%8C%E9%9D%A2/test_dummy.txt';

        $this->assertEquals($this->encoder->decode($safeString), $unsafeString);
    }
}
