<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use Closure;
use JsonException;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

use function is_array;
use function is_string;

/**
 * Custom environment variable processor that intelligently handles JSON data.
 *
 * Detects whether the value is:
 * - A file path ending with .json (reads file and parses JSON)
 * - Direct JSON content (parses inline)
 *
 * Usage: %env(smart_json:GEOLOC_OBJECTS)%
 */
final readonly class SmartJsonEnvVarProcessor implements EnvVarProcessorInterface
{
    public function __construct(
        private string $projectDir,
    ) {
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getEnv(string $prefix, string $name, Closure $getEnv): array
    {
        $value = $getEnv($name);

        if (null === $value || '' === $value) {
            return [];
        }

        if (!is_string($value)) {
            throw new RuntimeException(
                sprintf('Environment variable "%s" must be a string, got "%s".', $name, get_debug_type($value))
            );
        }

        $trimmedValue = trim($value);

        if ('' === $trimmedValue) {
            return [];
        }

        if (str_ends_with($trimmedValue, '.json')) {
            return $this->parseJsonFromFile($trimmedValue, $name);
        }

        return $this->parseJson($trimmedValue, $name, 'inline JSON');
    }

    public static function getProvidedTypes(): array
    {
        return [
            'smart_json' => 'array',
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    private function parseJson(string $json, string $varName, string $source): array
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(
                sprintf(
                    'Invalid JSON in environment variable "%s" (%s): %s',
                    $varName,
                    $source,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        if (!is_array($data)) {
            throw new RuntimeException(
                sprintf(
                    'Environment variable "%s" (%s) must decode to an array, got "%s".',
                    $varName,
                    $source,
                    get_debug_type($data)
                )
            );
        }

        return $data;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function parseJsonFromFile(string $filePath, string $varName): array
    {
        $resolvedPath = $this->resolveFilePath($filePath);

        if (!file_exists($resolvedPath)) {
            throw new RuntimeException(
                sprintf(
                    'File "%s" specified in environment variable "%s" does not exist.',
                    $filePath,
                    $varName
                )
            );
        }

        if (!is_file($resolvedPath)) {
            throw new RuntimeException(
                sprintf(
                    'Path "%s" specified in environment variable "%s" is not a file.',
                    $filePath,
                    $varName
                )
            );
        }

        $contents = @file_get_contents($resolvedPath);
        if (false === $contents) {
            throw new RuntimeException(
                sprintf(
                    'Failed to read file "%s" specified in environment variable "%s".',
                    $filePath,
                    $varName
                )
            );
        }

        return $this->parseJson($contents, $varName, sprintf('file: %s', $filePath));
    }

    private function resolveFilePath(string $filePath): string
    {
        if (str_starts_with($filePath, '/')) {
            return $filePath;
        }

        if (str_starts_with($filePath, './')) {
            return $this->projectDir . '/' . substr($filePath, 2);
        }

        return $this->projectDir . '/' . $filePath;
    }
}
