<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Parser;

use Tourze\DifyDsl\Nodes\AbstractNode;

/**
 * 测试用的自定义节点类
 */
class TestCustomNode extends AbstractNode
{
    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        $nodeData = $data['data'] ?? [];
        if (!is_array($nodeData)) {
            $nodeData = [];
        }

        /** @phpstan-ignore-next-line */
        return new static(
            id: is_string($data['id'] ?? null) ? $data['id'] : '',
            title: is_string($nodeData['title'] ?? null) ? $nodeData['title'] : 'Custom',
            description: is_string($nodeData['desc'] ?? null) ? $nodeData['desc'] : ''
        );
    }

    public function getNodeType(): string
    {
        return 'custom';
    }
}
