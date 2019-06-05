<?php

use SimpleS3\Helpers\File;

class FileTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function test_load_file()
    {
        $url = 'https://jsonplaceholder.typicode.com/photos';
        $content = json_decode(File::loadFile($url));

        $this->assertTrue(is_array($content));
        $this->assertCount(5000, $content);
    }

    /**
     * @test
     */
    public function get_file_info()
    {
        $path = '/usr/path/to/file.txt';

        $this->assertEquals(File::getInfo($path)['dirname'], '/usr/path/to');
        $this->assertEquals(File::getInfo($path)['basename'], 'file.txt');
        $this->assertEquals(File::getInfo($path)['extension'], 'txt');
        $this->assertEquals(File::getInfo($path)['filename'], 'file');
    }
}