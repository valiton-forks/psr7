<?php

declare(strict_types=1);

namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\LazyOpenStream;
use PHPUnit\Framework\TestCase;

class LazyOpenStreamTest extends TestCase
{
    private $fname;

    protected function setUp(): void
    {
        $this->fname = tempnam('/tmp', 'tfile');

        if (file_exists($this->fname)) {
            unlink($this->fname);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->fname)) {
            unlink($this->fname);
        }
    }

    public function testOpensLazily()
    {
        $l = new LazyOpenStream($this->fname, 'w+');
        $l->write('foo');
        self::assertIsArray($l->getMetadata());
        self::assertFileExists($this->fname);
        self::assertEquals('foo', file_get_contents($this->fname));
        self::assertEquals('foo', (string) $l);
    }

    public function testProxiesToFile()
    {
        file_put_contents($this->fname, 'foo');
        $l = new LazyOpenStream($this->fname, 'r');
        self::assertEquals('foo', $l->read(4));
        self::assertTrue($l->eof());
        self::assertEquals(3, $l->tell());
        self::assertTrue($l->isReadable());
        self::assertTrue($l->isSeekable());
        self::assertFalse($l->isWritable());
        $l->seek(1);
        self::assertEquals('oo', $l->getContents());
        self::assertEquals('foo', (string) $l);
        self::assertEquals(3, $l->getSize());
        self::assertIsArray($l->getMetadata());
        $l->close();
    }

    public function testDetachesUnderlyingStream()
    {
        file_put_contents($this->fname, 'foo');
        $l = new LazyOpenStream($this->fname, 'r');
        $r = $l->detach();
        self::assertIsResource($r);
        fseek($r, 0);
        self::assertEquals('foo', stream_get_contents($r));
        fclose($r);
    }
}
