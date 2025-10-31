<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Nodes;

/**
 * 大语言模型节点 - 调用 LLM 进行文本处理
 */
class LLMNode extends AbstractNode
{
    /** @var array<string, mixed> */
    private array $model = [];

    /** @var array<int, array<string, mixed>> */
    private array $promptTemplate = [];

    /** @var array<string, mixed>|null */
    private ?array $context = null;

    /** @var array<string, mixed>|null */
    private ?array $memory = null;

    /** @var array<string, mixed>|null */
    private ?array $vision = null;

    /** @var array<string, mixed> */
    private array $variables = [];

    private bool $structuredOutputEnabled = false;

    /** @var array<string, mixed>|null */
    private ?array $outputSchema = null;

    public function __construct(string $id, string $title = 'LLM', string $description = '')
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
            title: is_string($nodeData['title'] ?? null) ? $nodeData['title'] : 'LLM',
            description: is_string($nodeData['desc'] ?? null) ? $nodeData['desc'] : ''
        );

        self::setBaseProperties($node, $data);
        self::loadModelProperties($node, $nodeData);
        self::loadContextAndMemory($node, $nodeData);
        self::loadOutputConfiguration($node, $nodeData);

        return $node;
    }

    /**
     * @param array<string, mixed> $nodeData
     */
    private static function loadModelProperties(self $node, array $nodeData): void
    {
        if (isset($nodeData['model']) && is_array($nodeData['model'])) {
            /** @var array<string, mixed> $model */
            $model = $nodeData['model'];
            $node->setModelFromArray($model);
        }

        if (isset($nodeData['prompt_template']) && is_array($nodeData['prompt_template'])) {
            /** @var array<int, array<string, mixed>> $promptTemplate */
            $promptTemplate = $nodeData['prompt_template'];
            $node->setPromptTemplate($promptTemplate);
        }
    }

    /**
     * @param array<string, mixed> $nodeData
     */
    private static function loadContextAndMemory(self $node, array $nodeData): void
    {
        if (isset($nodeData['context']) && is_array($nodeData['context'])) {
            /** @var array<string, mixed> $context */
            $context = $nodeData['context'];
            $node->context = $context;
        }

        if (isset($nodeData['memory']) && is_array($nodeData['memory'])) {
            /** @var array<string, mixed> $memory */
            $memory = $nodeData['memory'];
            $node->setMemory($memory);
        }

        if (isset($nodeData['vision']) && is_array($nodeData['vision'])) {
            /** @var array<string, mixed> $vision */
            $vision = $nodeData['vision'];
            $node->vision = $vision;
        }

        if (isset($nodeData['variables']) && is_array($nodeData['variables'])) {
            /** @var array<string, mixed> $variables */
            $variables = $nodeData['variables'];
            $node->setVariables($variables);
        }
    }

    /**
     * @param array<string, mixed> $nodeData
     */
    private static function loadOutputConfiguration(self $node, array $nodeData): void
    {
        if (isset($nodeData['structured_output_enabled']) && is_bool($nodeData['structured_output_enabled']) && $nodeData['structured_output_enabled']) {
            $schema = $nodeData['output_schema'] ?? [];
            if (is_array($schema)) {
                /** @var array<string, mixed> $typedSchema */
                $typedSchema = $schema;
                $node->enableStructuredOutput($typedSchema);
            }
        }
    }

    /**
     * @param array<string, mixed> $model
     */
    public function setModelFromArray(array $model): void
    {
        $this->model = $model;
    }

    /** @param array<string, mixed> $schema */
    public function enableStructuredOutput(array $schema): self
    {
        $this->structuredOutputEnabled = true;
        $this->outputSchema = $schema;

        return $this;
    }

    public static function create(string $id = 'llm'): self
    {
        return new self($id);
    }

    public function getNodeType(): string
    {
        return 'llm';
    }

    /** @return array<string, mixed> */
    public function getModel(): array
    {
        return $this->model;
    }

    /** @param array<string, mixed> $completionParams */
    public function setModel(string $name, string $provider, string $mode = 'chat', array $completionParams = []): void
    {
        $this->model = [
            'mode' => $mode,
            'name' => $name,
            'provider' => $provider,
        ];

        if ([] !== $completionParams) {
            $this->model['completion_params'] = $completionParams;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function getPromptTemplate(): array
    {
        return $this->promptTemplate;
    }

    /**
     * @param array<int, array<string, mixed>> $promptTemplate
     */
    public function setPromptTemplate(array $promptTemplate): void
    {
        $this->promptTemplate = $promptTemplate;
    }

    public function addPromptMessage(string $role, string $text, ?string $editionType = null, ?string $id = null): self
    {
        $message = [
            'role' => $role,
            'text' => $text,
        ];

        if (null !== $editionType) {
            $message['edition_type'] = $editionType;
        }

        if (null !== $id) {
            $message['id'] = $id;
        }

        $this->promptTemplate[] = $message;

        return $this;
    }

    public function setSystemPrompt(string $prompt): void
    {
        $this->promptTemplate = array_filter(
            $this->promptTemplate,
            fn (array $msg) => 'system' !== $msg['role']
        );

        array_unshift($this->promptTemplate, [
            'role' => 'system',
            'text' => $prompt,
        ]);
    }

    public function setUserPrompt(string $prompt): void
    {
        $this->promptTemplate = array_filter(
            $this->promptTemplate,
            fn (array $msg) => 'user' !== $msg['role']
        );

        $this->promptTemplate[] = [
            'role' => 'user',
            'text' => $prompt,
        ];
    }

    /** @return array<string, mixed>|null */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /** @param array<string, mixed> $variableSelector */
    public function enableContext(array $variableSelector): self
    {
        $this->context = [
            'enabled' => true,
            'variable_selector' => $variableSelector,
        ];

        return $this;
    }

    public function disableContext(): self
    {
        $this->context = ['enabled' => false];

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getMemory(): ?array
    {
        return $this->memory;
    }

    /**
     * @param array<string, mixed> $memory
     */
    public function setMemory(array $memory): void
    {
        $this->memory = $memory;
    }

    /** @return array<string, mixed>|null */
    public function getVision(): ?array
    {
        return $this->vision;
    }

    /** @param array<string, mixed> $variableSelector */
    public function enableVision(array $variableSelector, string $detail = 'auto'): self
    {
        $this->vision = [
            'enabled' => true,
            'configs' => [
                'detail' => $detail,
                'variable_selector' => $variableSelector,
            ],
        ];

        return $this;
    }

    public function disableVision(): self
    {
        $this->vision = ['enabled' => false];

        return $this;
    }

    /** @return array<string, mixed> */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function setVariables(array $variables): void
    {
        $this->variables = $variables;
    }

    public function isStructuredOutputEnabled(): bool
    {
        return $this->structuredOutputEnabled;
    }

    public function disableStructuredOutput(): self
    {
        $this->structuredOutputEnabled = false;
        $this->outputSchema = null;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getOutputSchema(): ?array
    {
        return $this->outputSchema;
    }

    protected function getNodeData(): array
    {
        $data = parent::getNodeData();

        if ([] !== $this->model) {
            $data['model'] = $this->model;
        }

        if ([] !== $this->promptTemplate) {
            $data['prompt_template'] = $this->promptTemplate;
        }

        if (null !== $this->context) {
            $data['context'] = $this->context;
        }

        if (null !== $this->memory) {
            $data['memory'] = $this->memory;
        }

        if (null !== $this->vision) {
            $data['vision'] = $this->vision;
        }

        if ([] !== $this->variables) {
            $data['variables'] = $this->variables;
        }

        if ($this->structuredOutputEnabled) {
            $data['structured_output_enabled'] = true;
            if (null !== $this->outputSchema) {
                $data['output_schema'] = $this->outputSchema;
            }
        }

        return $data;
    }
}
