<?php
namespace Finxp\Flexcube\Tests\Unit\Services\FileGenerator;

use Finxp\Flexcube\Tests\TestCase;
use Finxp\Flexcube\Services\FileGenerator\PDFGenerator;
use Finxp\Flexcube\Contracts\FileGenerator;

class PDFGeneratorTest extends TestCase
{
    /** @test */
    public function itShouldImplementFileGenerator()
    {
        $pdfGenerator = new PDFGenerator();
        $this->assertTrue($pdfGenerator instanceof FileGenerator);
    }
}