<?php

use Matecat\SimpleS3\Helpers\File;
use Matecat\SimpleS3\Helpers\Filename;

class FilenameTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function does_not_reduce_filename_under_221_bytes()
    {
        $file  = "ГУЛИСТАН_КНС_10___SEP___100.03_Дефектный_акт_.xls.sdlxliff";
        $safe = Filename::getSafe($file);

        $this->assertEquals($file, $safe);
        $this->assertEquals(strlen($file), strlen($safe));
    }

    /**
     * @test
     */
    public function can_reduce_a_long_filename_to_a_221_bytes_long_name_keeping_the_original_extension()
    {
        $fileTooLong = "/tmp/5ddfab5e8a6aa9.45666312_.out.Package_2_-_Инженерная_оценка_Гулистан_КНС_9-10-11.zip___SEP___Package_2_-_Инженерная_оценка_Гулистан_КНС_9-10-11___SEP___ГУЛИСТАН_КНС_10___SEP___100.03_Дефектный_акт_.xls.sdlxliff";
        $safe = Filename::getSafe($fileTooLong);

        $this->assertEquals(221, strlen($safe));
        $this->assertEquals(File::getExtension($fileTooLong), File::getExtension($safe));
    }
}
