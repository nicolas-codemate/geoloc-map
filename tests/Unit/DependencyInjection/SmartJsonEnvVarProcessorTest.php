<?php

declare(strict_types=1);

namespace App\Tests\Unit\DependencyInjection;

use App\DependencyInjection\SmartJsonEnvVarProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

class SmartJsonEnvVarProcessorTest extends TestCase
{
    private SmartJsonEnvVarProcessor $processor;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/smart_json_test_' . uniqid();
        mkdir($this->tempDir);
        $this->processor = new SmartJsonEnvVarProcessor($this->tempDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    #[DataProvider('successfulCasesProvider')]
    public function testProcessesValueSuccessfully(string|callable $envValue, callable $setup, array $expectedResult): void
    {
        $setup($this->tempDir);

        $actualEnvValue = is_callable($envValue) ? $envValue($this->tempDir) : $envValue;
        $result = $this->processor->getEnv('smart_json', 'TEST_VAR', fn() => $actualEnvValue);

        $this->assertSame($expectedResult, $result);
    }

    public static function successfulCasesProvider(): iterable
    {
        yield 'inline JSON with array' => [
            '[{"mapName":"test","latitude":48.8575}]',
            fn() => null,
            [['mapName' => 'test', 'latitude' => 48.8575]],
        ];

        yield 'inline JSON empty array' => [
            '[]',
            fn() => null,
            [],
        ];

        yield 'file with absolute path' => [
            fn($tempDir) => $tempDir . '/absolute.json',
            function ($tempDir) {
                file_put_contents($tempDir . '/absolute.json', '[{"from":"file"}]');
            },
            [['from' => 'file']],
        ];

        yield 'file with relative path (./)' => [
            './config.json',
            function ($tempDir) {
                file_put_contents($tempDir . '/config.json', '[{"type":"relative_dot"}]');
            },
            [['type' => 'relative_dot']],
        ];

        yield 'file with relative path (no ./)' => [
            'data.json',
            function ($tempDir) {
                file_put_contents($tempDir . '/data.json', '[{"type":"relative"}]');
            },
            [['type' => 'relative']],
        ];

        yield 'empty string returns empty array' => [
            '',
            fn() => null,
            [],
        ];

        yield 'whitespace string returns empty array' => [
            '   ',
            fn() => null,
            [],
        ];
    }

    public function testReturnsEmptyArrayForNullValue(): void
    {
        $result = $this->processor->getEnv('smart_json', 'TEST_VAR', fn() => null);

        $this->assertSame([], $result);
    }

    public function testThrowsExceptionForInvalidInlineJson(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON in environment variable "TEST_VAR" (inline JSON)');

        $this->processor->getEnv('smart_json', 'TEST_VAR', fn() => '[invalid json');
    }

    public function testThrowsExceptionForNonArrayInlineJson(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must decode to an array');

        $this->processor->getEnv('smart_json', 'TEST_VAR', fn() => '"string value"');
    }

    public function testThrowsExceptionForNonExistentFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $this->processor->getEnv('smart_json', 'TEST_VAR', fn() => '/nonexistent/file.json');
    }

    public function testThrowsExceptionForDirectory(): void
    {
        $dirPath = $this->tempDir . '/config.json';
        mkdir($dirPath);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not a file');

        $this->processor->getEnv('smart_json', 'TEST_VAR', fn() => 'config.json');
    }

    public function testThrowsExceptionForInvalidJsonInFile(): void
    {
        $filePath = 'invalid.json';
        file_put_contents($this->tempDir . '/invalid.json', '[invalid json content');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON in environment variable "TEST_VAR" (file: invalid.json)');

        $this->processor->getEnv('smart_json', 'TEST_VAR', fn() => $filePath);
    }

    public function testThrowsExceptionForNonArrayJsonInFile(): void
    {
        file_put_contents($this->tempDir . '/string.json', '"just a string"');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must decode to an array');

        $this->processor->getEnv('smart_json', 'TEST_VAR', fn() => 'string.json');
    }

    public function testThrowsExceptionForNonStringValue(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must be a string');

        $this->processor->getEnv('smart_json', 'TEST_VAR', fn() => 123);
    }

    public function testGetProvidedTypes(): void
    {
        $types = SmartJsonEnvVarProcessor::getProvidedTypes();

        $this->assertArrayHasKey('smart_json', $types);
        $this->assertSame('array', $types['smart_json']);
    }
}
