<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Core;

/**
 * 表示 Dify DSL 中的变量定义
 */
class Variable
{
    public function __construct(
        private string $variable,
        private string $label,
        private string $type,
        private bool $required = false,
        private ?string $description = null,
        private mixed $defaultValue = null,
        private ?int $maxLength = null,
        /** @var array<string, mixed> */
        private array $options = [],
        /** @var array<string> */
        private array $allowedFileExtensions = [],
        /** @var array<string> */
        private array $allowedFileTypes = [],
        /** @var array<string> */
        private array $allowedFileUploadMethods = [],
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            variable: self::extractString($data, 'variable', ''),
            label: self::extractString($data, 'label', ''),
            type: self::extractString($data, 'type', 'text-input'),
            required: self::extractBool($data, 'required', false),
            description: self::extractNullableString($data, 'description'),
            defaultValue: $data['default'] ?? null,
            maxLength: self::extractInt($data, 'max_length'),
            options: self::extractMixedArray($data, 'options'),
            allowedFileExtensions: self::extractStringArray($data, 'allowed_file_extensions'),
            allowedFileTypes: self::extractStringArray($data, 'allowed_file_types'),
            allowedFileUploadMethods: self::extractStringArray($data, 'allowed_file_upload_methods')
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function extractString(array $data, string $key, string $default): string
    {
        return isset($data[$key]) && is_string($data[$key]) ? $data[$key] : $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function extractNullableString(array $data, string $key): ?string
    {
        return isset($data[$key]) && is_string($data[$key]) ? $data[$key] : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function extractBool(array $data, string $key, bool $default): bool
    {
        return isset($data[$key]) && is_bool($data[$key]) ? $data[$key] : $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function extractInt(array $data, string $key): ?int
    {
        return isset($data[$key]) && is_int($data[$key]) ? $data[$key] : null;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function extractMixedArray(array $data, string $key): array
    {
        /** @var array<string, mixed> */
        return isset($data[$key]) && is_array($data[$key]) ? $data[$key] : [];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string>
     */
    private static function extractStringArray(array $data, string $key): array
    {
        /** @var array<string> */
        return isset($data[$key]) && is_array($data[$key]) ? $data[$key] : [];
    }

    public function getVariable(): string
    {
        return $this->variable;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    /** @return array<string, mixed> */
    public function getOptions(): array
    {
        return $this->options;
    }

    /** @return array<string> */
    public function getAllowedFileExtensions(): array
    {
        return $this->allowedFileExtensions;
    }

    /** @return array<string> */
    public function getAllowedFileTypes(): array
    {
        return $this->allowedFileTypes;
    }

    /** @return array<string> */
    public function getAllowedFileUploadMethods(): array
    {
        return $this->allowedFileUploadMethods;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            'variable' => $this->variable,
            'label' => $this->label,
            'type' => $this->type,
            'required' => $this->required,
        ];

        if (null !== $this->description) {
            $data['description'] = $this->description;
        }

        if (null !== $this->defaultValue) {
            $data['default'] = $this->defaultValue;
        }

        if (null !== $this->maxLength) {
            $data['max_length'] = $this->maxLength;
        }

        if ([] !== $this->options) {
            $data['options'] = $this->options;
        }

        if ([] !== $this->allowedFileExtensions) {
            $data['allowed_file_extensions'] = $this->allowedFileExtensions;
        }

        if ([] !== $this->allowedFileTypes) {
            $data['allowed_file_types'] = $this->allowedFileTypes;
        }

        if ([] !== $this->allowedFileUploadMethods) {
            $data['allowed_file_upload_methods'] = $this->allowedFileUploadMethods;
        }

        return $data;
    }
}
