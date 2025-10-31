<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Nodes;

use Tourze\DifyDsl\Nodes\AbstractNode;

/**
 * 具体的测试节点实现，用于测试抽象基类
 */
class TestNode extends AbstractNode
{
    /** @var array<string, mixed> */
    private array $testData = [];

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        $nodeData = $data['data'] ?? [];
        if (!is_array($nodeData)) {
            $nodeData = [];
        }

        /** @phpstan-ignore-next-line */
        $node = new static(
            id: is_string($data['id'] ?? null) ? $data['id'] : '',
            title: is_string($nodeData['title'] ?? null) ? $nodeData['title'] : '',
            description: is_string($nodeData['desc'] ?? null) ? $nodeData['desc'] : ''
        );

        self::setBaseProperties($node, $data);

        if (isset($nodeData['testData']) && is_array($nodeData['testData'])) {
            /** @var array<string, mixed> $testData */
            $testData = $nodeData['testData'];
            $node->testData = $testData;
        }

        return $node;
    }

    public function getNodeType(): string
    {
        return 'test';
    }

    /** @param array<string, mixed> $data */
    public function setTestData(array $data): void
    {
        $this->testData = $data;
    }

    /** @return array<string, mixed> */
    public function getTestData(): array
    {
        return $this->testData;
    }

    protected function getNodeData(): array
    {
        $data = parent::getNodeData();

        if ([] !== $this->testData) {
            $data['testData'] = $this->testData;
        }

        return $data;
    }
}
