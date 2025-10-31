<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Nodes;

/**
 * 工具节点 - 调用内置或自定义工具
 */
class ToolNode extends AbstractNode
{
    private string $providerId = '';

    private string $providerName = '';

    private string $providerType = '';

    private string $toolName = '';

    private string $toolLabel = '';

    private string $toolDescription = '';

    /** @var array<string, mixed> */
    private array $toolParameters = [];

    /** @var array<string, mixed> */
    private array $paramSchemas = [];

    /** @var array<string, mixed> */
    private array $toolConfigurations = [];

    private bool $isTeamAuthorization = false;

    /** @var array<string, mixed>|null */
    private ?array $retryConfig = null;

    public function __construct(string $id, string $title = '工具', string $description = '')
    {
        parent::__construct($id, $title, $description);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        $nodeData = $data['data'] ?? [];
        if (!is_array($nodeData)) {
            $nodeData = [];
        }
        /** @var array<string, mixed> $nodeData */

        /** @phpstan-ignore-next-line */
        $node = new static(
            id: is_string($data['id'] ?? null) ? $data['id'] : '',
            title: is_string($nodeData['title'] ?? null) ? $nodeData['title'] : '工具',
            description: is_string($nodeData['desc'] ?? null) ? $nodeData['desc'] : ''
        );

        self::setBaseProperties($node, $data);
        self::loadProviderAndTool($node, $nodeData);
        self::loadToolConfiguration($node, $nodeData);

        return $node;
    }

    /**
     * @param array<string, mixed> $nodeData
     */
    private static function loadProviderAndTool(self $node, array $nodeData): void
    {
        self::loadProvider($node, $nodeData);
        self::loadTool($node, $nodeData);
        self::loadToolParameters($node, $nodeData);
    }

    /**
     * @param array<string, mixed> $nodeData
     */
    private static function loadProvider(self $node, array $nodeData): void
    {
        if (isset($nodeData['provider_id'], $nodeData['provider_name'], $nodeData['provider_type'])
            && is_string($nodeData['provider_id'])
            && is_string($nodeData['provider_name'])
            && is_string($nodeData['provider_type'])) {
            $node->setProvider(
                $nodeData['provider_id'],
                $nodeData['provider_name'],
                $nodeData['provider_type']
            );
        }
    }

    /**
     * @param array<string, mixed> $nodeData
     */
    private static function loadTool(self $node, array $nodeData): void
    {
        if (isset($nodeData['tool_name']) && is_string($nodeData['tool_name'])) {
            $node->setTool(
                $nodeData['tool_name'],
                is_string($nodeData['tool_label'] ?? null) ? $nodeData['tool_label'] : '',
                is_string($nodeData['tool_description'] ?? null) ? $nodeData['tool_description'] : ''
            );
        }
    }

    /**
     * @param array<string, mixed> $nodeData
     */
    private static function loadToolParameters(self $node, array $nodeData): void
    {
        if (isset($nodeData['tool_parameters']) && is_array($nodeData['tool_parameters'])) {
            /** @var array<string, mixed> $toolParameters */
            $toolParameters = $nodeData['tool_parameters'];
            $node->setParameters($toolParameters);
        }
    }

    /**
     * @param array<string, mixed> $nodeData
     */
    private static function loadToolConfiguration(self $node, array $nodeData): void
    {
        if (isset($nodeData['param_schemas']) && is_array($nodeData['param_schemas'])) {
            /** @var array<string, mixed> $paramSchemas */
            $paramSchemas = $nodeData['param_schemas'];
            $node->paramSchemas = $paramSchemas;
        }

        if (isset($nodeData['tool_configurations']) && is_array($nodeData['tool_configurations'])) {
            /** @var array<string, mixed> $toolConfigurations */
            $toolConfigurations = $nodeData['tool_configurations'];
            $node->toolConfigurations = $toolConfigurations;
        }

        if (isset($nodeData['is_team_authorization']) && is_bool($nodeData['is_team_authorization'])) {
            $node->isTeamAuthorization = $nodeData['is_team_authorization'];
        }

        if (isset($nodeData['retry_config']) && is_array($nodeData['retry_config'])) {
            /** @var array<string, mixed> $retryConfig */
            $retryConfig = $nodeData['retry_config'];
            $node->retryConfig = $retryConfig;
        }
    }

    public function setProvider(string $id, string $name, string $type): void
    {
        $this->providerId = $id;
        $this->providerName = $name;
        $this->providerType = $type;
    }

    public function setTool(string $name, string $label = '', string $description = ''): void
    {
        $this->toolName = $name;
        $this->toolLabel = '' !== $label ? $label : $name;
        $this->toolDescription = $description;
    }

    /** @param array<string, mixed> $parameters */
    public function setParameters(array $parameters): void
    {
        $this->toolParameters = $parameters;
    }

    public static function create(string $id = 'tool'): self
    {
        return new self($id);
    }

    public function getNodeType(): string
    {
        return 'tool';
    }

    public function addParameter(string $name, mixed $value): self
    {
        $this->toolParameters[$name] = [
            'type' => 'mixed',
            'value' => $value,
        ];

        return $this;
    }

    public function enableRetry(int $maxRetries = 3, int $retryInterval = 1000): self
    {
        $this->retryConfig = [
            'retry_enabled' => true,
            'max_retries' => $maxRetries,
            'retry_interval' => $retryInterval,
        ];

        return $this;
    }

    protected function getNodeData(): array
    {
        $data = parent::getNodeData();

        return array_merge($data, $this->getProviderData(), $this->getToolData(), $this->getConfigurationData());
    }

    /** @return array<string, mixed> */
    private function getProviderData(): array
    {
        $data = [];
        if ('' !== $this->providerId) {
            $data['provider_id'] = $this->providerId;
        }
        if ('' !== $this->providerName) {
            $data['provider_name'] = $this->providerName;
        }
        if ('' !== $this->providerType) {
            $data['provider_type'] = $this->providerType;
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function getToolData(): array
    {
        $data = [];
        if ('' !== $this->toolName) {
            $data['tool_name'] = $this->toolName;
        }
        if ('' !== $this->toolLabel) {
            $data['tool_label'] = $this->toolLabel;
        }
        if ('' !== $this->toolDescription) {
            $data['tool_description'] = $this->toolDescription;
        }
        if ([] !== $this->toolParameters) {
            $data['tool_parameters'] = $this->toolParameters;
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function getConfigurationData(): array
    {
        $data = [];
        if ([] !== $this->paramSchemas) {
            $data['paramSchemas'] = $this->paramSchemas;
        }
        if ([] !== $this->toolConfigurations) {
            $data['tool_configurations'] = $this->toolConfigurations;
        }
        if ($this->isTeamAuthorization) {
            $data['is_team_authorization'] = true;
        }
        if (null !== $this->retryConfig) {
            $data['retry_config'] = $this->retryConfig;
        }

        return $data;
    }
}
