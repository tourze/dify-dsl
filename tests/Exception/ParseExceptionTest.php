<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyDsl\Exception\ParseException;

/**
 * @internal
 * @phpstan-ignore-next-line
 */
#[CoversClass(ParseException::class)]
class ParseExceptionTest extends TestCase
{
    public function testCreateParseException(): void
    {
        $message = 'Test parse error';
        $exception = new ParseException($message);

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testCreateParseExceptionWithCode(): void
    {
        $message = 'Parse error with code';
        $code = 500;
        $exception = new ParseException($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testCreateParseExceptionWithPrevious(): void
    {
        $previousException = new \RuntimeException('Previous error');
        $message = 'Parse error with previous';
        $exception = new ParseException($message, 0, $previousException);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testParseExceptionInheritance(): void
    {
        $exception = new ParseException('Inheritance test');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(ParseException::class, $exception);
    }
}
