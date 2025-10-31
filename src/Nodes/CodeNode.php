<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Nodes;

/**
 * 代码执行节点 - 执行 Python 或 JavaScript 代码
 */
class CodeNode extends AbstractNode
{
    private string $codeLanguage = 'python3';

    private string $code = '';

    /** @var array<int, array{variable: string, value_selector: array<string, mixed>}> */
    private array $variables = [];

    /** @var array<string, array{type: string, children?: array<string, mixed>}> */
    private array $outputs = [];

    public function __construct(string $id, string $title = '代码执行', string $description = '')
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
            title: is_string($nodeData['title'] ?? null) ? $nodeData['title'] : '代码执行',
            description: is_string($nodeData['desc'] ?? null) ? $nodeData['desc'] : ''
        );

        self::setBaseProperties($node, $data);
        self::loadCodeProperties($node, $nodeData);

        return $node;
    }

    /**
     * @param array<string, mixed> $nodeData
     */
    private static function loadCodeProperties(self $node, array $nodeData): void
    {
        if (isset($nodeData['code_language']) && is_string($nodeData['code_language'])) {
            $node->setCodeLanguage($nodeData['code_language']);
        }

        if (isset($nodeData['code']) && is_string($nodeData['code'])) {
            $node->setCode($nodeData['code']);
        }

        if (isset($nodeData['variables']) && is_array($nodeData['variables'])) {
            /** @var array<int, array{variable: string, value_selector: array<string, mixed>}> $variables */
            $variables = $nodeData['variables'];
            $node->setVariables($variables);
        }

        if (isset($nodeData['outputs']) && is_array($nodeData['outputs'])) {
            /** @var array<string, array{type: string, children?: array<string, mixed>}> $outputs */
            $outputs = $nodeData['outputs'];
            $node->setOutputs($outputs);
        }
    }

    public static function create(string $id = 'code'): self
    {
        return new self($id);
    }

    public function getNodeType(): string
    {
        return 'code';
    }

    public function getCodeLanguage(): string
    {
        return $this->codeLanguage;
    }

    public function setCodeLanguage(string $language): void
    {
        $this->codeLanguage = $language;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /** @return array<int, array{variable: string, value_selector: array<string, mixed>}> */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /** @param array<int, array{variable: string, value_selector: array<string, mixed>}> $variables */
    public function setVariables(array $variables): void
    {
        $this->variables = $variables;
    }

    /** @param array<string, mixed> $valueSelector */
    public function addVariable(string $variable, array $valueSelector): self
    {
        $this->variables[] = [
            'variable' => $variable,
            'value_selector' => $valueSelector,
        ];

        return $this;
    }

    /** @return array<string, array{type: string, children?: array<string, mixed>}> */
    public function getOutputs(): array
    {
        return $this->outputs;
    }

    /** @param array<string, array{type: string, children?: array<string, mixed>}> $outputs */
    public function setOutputs(array $outputs): void
    {
        $this->outputs = $outputs;
    }

    /** @param array<string, mixed>|null $children */
    public function addOutput(string $name, string $type, ?array $children = null): self
    {
        $output = ['type' => $type];
        if (null !== $children) {
            $output['children'] = $children;
        }
        $this->outputs[$name] = $output;

        return $this;
    }

    protected function getNodeData(): array
    {
        $data = parent::getNodeData();

        if ('' !== $this->codeLanguage) {
            $data['code_language'] = $this->codeLanguage;
        }

        if ('' !== $this->code) {
            $data['code'] = $this->code;
        }

        if ([] !== $this->variables) {
            $data['variables'] = $this->variables;
        }

        if ([] !== $this->outputs) {
            $data['outputs'] = $this->outputs;
        }

        return $data;
    }
}
