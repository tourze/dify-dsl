<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Core;

/**
 * 表示 Dify DSL 应用的顶层对象
 */
class App
{
    public function __construct(
        private string $name,
        private string $description,
        private string $mode,
        private Workflow $workflow,
        private string $kind = 'app',
        private string $version = '0.2.0',
        private string $icon = '🤖',
        private string $iconBackground = '#FFEAD5',
        private bool $useIconAsAnswerIcon = false,
        /** @var array<string, mixed> */
        private array $dependencies = [],
        /** @var array<string, mixed>|null */
        private ?array $modelConfig = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        // Validate and extract app data
        $appData = self::extractArrayValue($data, 'app');

        // Validate and extract workflow data
        $workflowData = self::extractArrayValue($data, 'workflow');

        // Validate and extract dependencies
        $dependencies = self::extractArrayValue($data, 'dependencies');

        // Validate model config
        $modelConfig = self::extractOptionalArrayValue($data, 'model_config');

        return new self(
            name: self::extractStringValue($appData, 'name', ''),
            description: self::extractStringValue($appData, 'description', ''),
            mode: self::extractStringValue($appData, 'mode', 'workflow'),
            workflow: Workflow::fromArray($workflowData),
            kind: self::extractStringValue($data, 'kind', 'app'),
            version: self::extractStringValue($data, 'version', '0.2.0'),
            icon: self::extractStringValue($appData, 'icon', '🤖'),
            iconBackground: self::extractStringValue($appData, 'icon_background', '#FFEAD5'),
            useIconAsAnswerIcon: self::extractBoolValue($appData, 'use_icon_as_answer_icon', false),
            dependencies: $dependencies,
            modelConfig: $modelConfig
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function extractArrayValue(array $data, string $key): array
    {
        $value = $data[$key] ?? [];
        if (!is_array($value)) {
            return [];
        }

        /** @var array<string, mixed> */
        return $value;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    private static function extractOptionalArrayValue(array $data, string $key): ?array
    {
        $value = $data[$key] ?? null;
        if (null === $value) {
            return null;
        }
        if (!is_array($value)) {
            return null;
        }

        /** @var array<string, mixed> */
        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function extractStringValue(array $data, string $key, string $default): string
    {
        $value = $data[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function extractBoolValue(array $data, string $key, bool $default): bool
    {
        $value = $data[$key] ?? $default;

        return is_bool($value) ? $value : $default;
    }

    public static function create(string $name, string $mode = 'workflow'): self
    {
        return new self(
            name: $name,
            description: '',
            mode: $mode,
            workflow: new Workflow(new Graph())
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
    }

    public function getIconBackground(): string
    {
        return $this->iconBackground;
    }

    public function setIconBackground(string $iconBackground): void
    {
        $this->iconBackground = $iconBackground;
    }

    public function isUseIconAsAnswerIcon(): bool
    {
        return $this->useIconAsAnswerIcon;
    }

    public function setUseIconAsAnswerIcon(bool $useIconAsAnswerIcon): void
    {
        $this->useIconAsAnswerIcon = $useIconAsAnswerIcon;
    }

    public function getWorkflow(): Workflow
    {
        return $this->workflow;
    }

    public function setWorkflow(Workflow $workflow): void
    {
        $this->workflow = $workflow;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * @param string $key
     * @param array<string, mixed> $dependency
     */
    public function addDependency(string $key, array $dependency): self
    {
        $this->dependencies[$key] = $dependency;

        return $this;
    }

    /**
     * @param array<string, mixed> $dependencies
     */
    public function setDependencies(array $dependencies): void
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getModelConfig(): ?array
    {
        return $this->modelConfig;
    }

    /**
     * @param array<string, mixed>|null $modelConfig
     */
    public function setModelConfig(?array $modelConfig): void
    {
        $this->modelConfig = $modelConfig;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'app' => [
                'name' => $this->name,
                'description' => $this->description,
                'icon' => $this->icon,
                'icon_background' => $this->iconBackground,
                'mode' => $this->mode,
                'use_icon_as_answer_icon' => $this->useIconAsAnswerIcon,
            ],
            'kind' => $this->kind,
            'version' => $this->version,
            'workflow' => $this->workflow->toArray(),
        ];

        if ([] !== $this->dependencies) {
            $data['dependencies'] = $this->dependencies;
        }

        if (null !== $this->modelConfig) {
            $data['model_config'] = $this->modelConfig;
        }

        return $data;
    }
}
