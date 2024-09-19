<?php
namespace Finxp\Flexcube\Tests\Unit\Services\FileGenerator;

use Finxp\Flexcube\Tests\TestCase;
use Finxp\Flexcube\Services\FileGenerator\FileGeneratorFactory;
use Finxp\Flexcube\Services\FileGenerator\PDFGenerator;

class FileGeneratorFactoryTest extends TestCase
{
    /** @test */
    public function itShouldGetFileGenerator()
    {
        $generatorFactory = FileGeneratorFactory::getFileGenerator('pdf');
        $generatorFactory->render([]);

        $this->assertTrue($generatorFactory instanceof PDFGenerator);
    }
}