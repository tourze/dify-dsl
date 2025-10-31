<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Parser;

/**
 * 无效的节点类（不继承 AbstractNode）
 */
class InvalidNode
{
    public function getNodeType(): string
    {
        return 'invalid';
    }
}
