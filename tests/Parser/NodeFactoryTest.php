<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Parser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyDsl\Exception\ParseException;
use Tourze\DifyDsl\Nodes\AnswerNode;
use Tourze\DifyDsl\Nodes\CodeNode;
use Tourze\DifyDsl\Nodes\EndNode;
use Tourze\DifyDsl\Nodes\LLMNode;
use Tourze\DifyDsl\Nodes\StartNode;
use Tourze\DifyDsl\Nodes\ToolNode;
use Tourze\DifyDsl\Parser\NodeFactory;

/**
 * @internal
 */
#[CoversClass(NodeFactory::class)]
class NodeFactoryTest extends TestCase
{
    public function testCreateStartNode(): void
    {
        $data = [
            'id' => 'start_node',
            'data' => [
                'type' => 'start',
                'title' => 'Start Node',
                'desc' => 'Starting point',
            ],
        ];

        $node = NodeFactory::createFromArray($data);

        $this->assertInstanceOf(StartNode::class, $node);
        $this->assertEquals('start_node', $node->getId());
        $this->assertEquals('Start Node', $node->getTitle());
        $this->assertEquals('Starting point', $node->getDescription());
        $this->assertEquals('start', $node->getNodeType());
    }

    public function testCreateEndNode(): void
    {
        $data = [
            'id' => 'end_node',
            'data' => [
                'type' => 'end',
                'title' => 'End Node',
                'desc' => 'Ending point',
            ],
        ];

        $node = NodeFactory::createFromArray($data);

        $this->assertInstanceOf(EndNode::class, $node);
        $this->assertEquals('end_node', $node->getId());
        $this->assertEquals('End Node', $node->getTitle());
        $this->assertEquals('end', $node->getNodeType());
    }

    public function testCreateLLMNode(): void
    {
        $data = [
            'id' => 'llm_node',
            'data' => [
                'type' => 'llm',
                'title' => 'LLM Node',
                'model' => [
                    'name' => 'gpt-4',
                    'provider' => 'openai',
                ],
            ],
        ];

        $node = NodeFactory::createFromArray($data);

        $this->assertInstanceOf(LLMNode::class, $node);
        $this->assertEquals('llm_node', $node->getId());
        $this->assertEquals('llm', $node->getNodeType());
    }

    public function testCreateFromArrayWithUnsupportedType(): void
    {
        $data = [
            'id' => 'unknown_node',
            'data' => [
                'type' => 'unknown',
                'title' => 'Unknown Node',
            ],
        ];

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unsupported node type: unknown');

        NodeFactory::createFromArray($data);
    }

    public function testCreateFromArrayWithMissingType(): void
    {
        $data = [
            'id' => 'no_type_node',
            'data' => [
                'title' => 'No Type Node',
            ],
        ];

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unsupported node type: ');

        NodeFactory::createFromArray($data);
    }

    public function testRegisterNodeType(): void
    {
        NodeFactory::registerNodeType('custom', TestCustomNode::class);

        $data = [
            'id' => 'custom_node',
            'data' => [
                'type' => 'custom',
                'title' => 'Custom Node',
                'desc' => 'A custom node',
            ],
        ];

        $node = NodeFactory::createFromArray($data);

        $this->assertInstanceOf(TestCustomNode::class, $node);
        $this->assertEquals('custom_node', $node->getId());
        $this->assertEquals('Custom Node', $node->getTitle());
        $this->assertEquals('custom', $node->getNodeType());
    }

    public function testRegisterInvalidNodeType(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Class ' . InvalidNode::class . ' must extend AbstractNode');

        NodeFactory::registerNodeType('invalid', InvalidNode::class);
    }

    public function testGetSupportedTypes(): void
    {
        $supportedTypes = NodeFactory::getSupportedTypes();

        $expectedTypes = ['start', 'end', 'answer', 'llm', 'tool', 'code'];

        foreach ($expectedTypes as $type) {
            $this->assertContains($type, $supportedTypes);
        }
    }

    public function testIsTypeSupported(): void
    {
        $this->assertTrue(NodeFactory::isTypeSupported('start'));
        $this->assertTrue(NodeFactory::isTypeSupported('end'));
        $this->assertTrue(NodeFactory::isTypeSupported('llm'));
        $this->assertTrue(NodeFactory::isTypeSupported('tool'));

        $this->assertFalse(NodeFactory::isTypeSupported('unknown'));
        $this->assertFalse(NodeFactory::isTypeSupported(''));
    }

    public function testCreateAllSupportedNodeTypes(): void
    {
        $testCases = [
            'start' => StartNode::class,
            'end' => EndNode::class,
            'answer' => AnswerNode::class,
            'llm' => LLMNode::class,
            'tool' => ToolNode::class,
            'code' => CodeNode::class,
        ];

        foreach ($testCases as $type => $expectedClass) {
            $data = [
                'id' => $type . '_test',
                'data' => [
                    'type' => $type,
                    'title' => ucfirst($type) . ' Test',
                ],
            ];

            $node = NodeFactory::createFromArray($data);

            $this->assertInstanceOf($expectedClass, $node);
            $this->assertEquals($type, $node->getNodeType());
            $this->assertEquals($type . '_test', $node->getId());
        }
    }
}
