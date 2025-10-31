<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Parser;

use Tourze\DifyDsl\Exception\ParseException;
use Tourze\DifyDsl\Nodes\AbstractNode;
use Tourze\DifyDsl\Nodes\AnswerNode;
use Tourze\DifyDsl\Nodes\CodeNode;
use Tourze\DifyDsl\Nodes\EndNode;
use Tourze\DifyDsl\Nodes\LLMNode;
use Tourze\DifyDsl\Nodes\StartNode;
use Tourze\DifyDsl\Nodes\ToolNode;

/**
 * 节点工厂 - 根据类型创建对应的节点实例
 */
class NodeFactory
{
    /** @var array<string, class-string<AbstractNode>> */
    private static array $nodeClassMap = [
        'start' => StartNode::class,
        'end' => EndNode::class,
        'answer' => AnswerNode::class,
        'llm' => LLMNode::class,
        'tool' => ToolNode::class,
        'code' => CodeNode::class,
        // 其他节点类型将在后续添加
    ];

    /** @param array<string, mixed> $data */
    public static function createFromArray(array $data): AbstractNode
    {
        $nodeData = isset($data['data']) && is_array($data['data']) ? $data['data'] : [];
        /** @var array<string, mixed> $nodeData */
        $nodeType = isset($nodeData['type']) && is_string($nodeData['type']) ? $nodeData['type'] : '';

        if (!isset(self::$nodeClassMap[$nodeType])) {
            throw new ParseException("Unsupported node type: {$nodeType}");
        }

        $nodeClass = self::$nodeClassMap[$nodeType];

        return $nodeClass::fromArray($data);
    }

    public static function registerNodeType(string $type, string $class): void
    {
        if (!is_subclass_of($class, AbstractNode::class)) {
            throw new ParseException("Class {$class} must extend AbstractNode");
        }

        self::$nodeClassMap[$type] = $class;
    }

    /** @return array<string> */
    public static function getSupportedTypes(): array
    {
        return array_keys(self::$nodeClassMap);
    }

    public static function isTypeSupported(string $type): bool
    {
        return isset(self::$nodeClassMap[$type]);
    }
}
