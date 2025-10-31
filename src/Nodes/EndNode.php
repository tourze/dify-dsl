<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Nodes;

/**
 * 结束节点 - 用于 Workflow，输出最终结果
 */
class EndNode extends AbstractNode
{
    /** @var array<int, array{variable: string, value_selector: list<string>}> */
    private array $outputs = [];

    public function __construct(string $id, string $title = '结束', string $description = '')
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
            title: is_string($nodeData['title'] ?? null) ? $nodeData['title'] : '结束',
            description: is_string($nodeData['desc'] ?? null) ? $nodeData['desc'] : ''
        );

        self::setBaseProperties($node, $data);

        if (isset($nodeData['outputs']) && is_array($nodeData['outputs'])) {
            /** @var array<int, array{variable: string, value_selector: list<string>}> $outputs */
            $outputs = $nodeData['outputs'];
            $node->setOutputs($outputs);
        }

        return $node;
    }

    public static function create(string $id = 'end'): self
    {
        return new self($id);
    }

    public function getNodeType(): string
    {
        return 'end';
    }

    /** @return array<int, array{variable: string, value_selector: list<string>}> */
    public function getOutputs(): array
    {
        return $this->outputs;
    }

    /** @param array<int, array{variable: string, value_selector: list<string>}> $outputs */
    public function setOutputs(array $outputs): void
    {
        $this->outputs = $outputs;
    }

    /** @param list<string> $valueSelector */
    public function addOutput(string $variable, array $valueSelector): self
    {
        $this->outputs[] = [
            'variable' => $variable,
            'value_selector' => $valueSelector,
        ];

        return $this;
    }

    protected function getNodeData(): array
    {
        $data = parent::getNodeData();

        if ([] !== $this->outputs) {
            $data['outputs'] = $this->outputs;
        }

        return $data;
    }
}
