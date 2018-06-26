<?php

namespace Honeybadger\Tests;

use Honeybadger\FileSource;
use PHPUnit\Framework\TestCase;

class FileSourceTest extends TestCase
{
    /** @test */
    public function it_failes_gracefully_if_there_is_no_file()
    {
        $fileSource = new FileSource('/dev/null/example.php', 0);

        $this->assertEquals([], $fileSource->getSource());
    }

    /** @test */
    public function it_returns_the_first_line_if_start_is_zero()
    {
        $source = (new FileSource(__DIR__.'/Fixtures/FileSourceFixture.php', 0))->getSource();

        $this->assertEquals('<?php', $source[1]);
        $this->assertEquals('namespace Honeybadger\Tests\Fixtures;', $source[3]);
    }

    /** @test */
    public function it_returns_the_first_line_if_start_is_negative()
    {
        $source = (new FileSource(__DIR__.'/Fixtures/FileSourceFixture.php', -1))->getSource();

        $this->assertEquals('<?php', $source[1]);
        $this->assertEquals('namespace Honeybadger\Tests\Fixtures;', $source[3]);
    }

    /** @test */
    public function it_can_get_a_specfic_set_of_source()
    {
        $source = (new FileSource(__DIR__.'/Fixtures/FileSourceFixture.php', 5))->getSource();

        $this->assertEquals('class FileSourceFixture', $source[5]);
    }

    /** @test */
    public function it_can_use_a_different_radius()
    {
        $source = (new FileSource(__DIR__.'/Fixtures/FileSourceFixture.php', 5, 6))->getSource();

        $this->assertEquals('<?php', $source[1]);
        $this->assertEquals('class FileSourceFixture', $source[5]);
        $this->assertEquals('    public function __construct()', $source['7']);
        $this->assertEquals('}', $source['11']);
    }
}
