<?php

use Matecat\SimpleS3\Helpers\File;
use Matecat\SimpleS3\Helpers\FilenameTrimmer;

class FilenameTrimmerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function does_not_reduce_filename_under_221_bytes()
    {
        $file  = "ГУЛИСТАН_КНС_10___SEP___100.03_Дефектный_акт_.xls.sdlxliff";
        $trim = (new FilenameTrimmer())->trim($file);

        $this->assertEquals($file, $trim);
        $this->assertEquals(strlen($file), strlen($trim));
    }

    /**
     * @test
     */
    public function can_reduce_a_long_filename_to_a_221_bytes_long_name_keeping_the_original_extension()
    {
        $fileTooLong = "/tmp/5ddfab5e8a6aa9.45666312_.out.Package_2_-_Инженерная_оценка_Гулистан_КНС_9-10-11.zip___SEP___Package_2_-_Инженерная_оценка_Гулистан_КНС_9-10-11___SEP___ГУЛИСТАН_КНС_10___SEP___100.03_Дефектный_акт_.xls.sdlxliff";
        $trim = (new FilenameTrimmer())->trim($fileTooLong);

        $this->assertEquals(221, strlen($trim));
        $this->assertEquals(File::getExtension($fileTooLong), File::getExtension($trim));
    }

    /**
     * @test
     */
    public function can_reduce_a_long_filename_to_a_10_bytes_long_name_keeping_the_original_extension()
    {
        $fileTooLong  = "ГУЛИСТАН_КНС_10___SEP___100.ГУЛИСТАН_КНС_10___SEP___100.03_Дефектный_акт_.xls.sdlxliff";
        $trim = (new FilenameTrimmer(128))->trim($fileTooLong);

        $this->assertEquals(94, strlen($trim));
        $this->assertEquals(File::getExtension($fileTooLong), File::getExtension($trim));
    }
}
