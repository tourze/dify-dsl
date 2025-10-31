<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Nodes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyDsl\Nodes\AbstractNode;

// 直接加载依赖文件
require_once __DIR__ . '/../../src/Nodes/AbstractNode.php';
require_once __DIR__ . '/TestNode.php';

/**
 * @internal
 */
#[CoversClass(AbstractNode::class)]
class AbstractNodeTest extends TestCase
{
    public function testCreateNode(): void
    {
        $node = new TestNode('test_id', 'Test Title', 'Test Description', 'custom');

        $this->assertEquals('test_id', $node->getId());
        $this->assertEquals('Test Title', $node->getTitle());
        $this->assertEquals('Test Description', $node->getDescription());
        $this->assertEquals(['x' => 0, 'y' => 0], $node->getPosition());
    }

    public function testCreateNodeWithDefaults(): void
    {
        $node = new TestNode('minimal');

        $this->assertEquals('minimal', $node->getId());
        $this->assertEquals('', $node->getTitle());
        $this->assertEquals('', $node->getDescription());
    }

    public function testSetTitle(): void
    {
        $node = new TestNode('test');

        $node->setTitle('New Title');

        $this->assertEquals('New Title', $node->getTitle());
    }

    public function testSetDescription(): void
    {
        $node = new TestNode('test');

        $node->setDescription('New Description');

        $this->assertEquals('New Description', $node->getDescription());
    }

    public function testSetPosition(): void
    {
        $node = new TestNode('test');

        $node->setPosition(100, 200);

        $this->assertEquals(['x' => 100, 'y' => 200], $node->getPosition());
    }

    public function testSetSelected(): void
    {
        $node = new TestNode('test');

        $node->setSelected(true);

        // 没有直接的getter，通过toArray验证
        $array = $node->toArray();
        $this->assertTrue($array['selected']);
    }

    public function testSetParentId(): void
    {
        $node = new TestNode('test');

        $node->setParentId('parent_node');

        // 通过toArray验证
        $array = $node->toArray();
        $this->assertEquals('parent_node', $array['parentId']);
    }

    public function testSetExtent(): void
    {
        $node = new TestNode('test');

        $node->setExtent('parent');

        // 通过toArray验证
        $array = $node->toArray();
        $this->assertEquals('parent', $array['extent']);
    }

    public function testFromArrayBasic(): void
    {
        $data = [
            'id' => 'node1',
            'type' => 'custom',
            'position' => ['x' => 50, 'y' => 100],
            'data' => [
                'title' => 'Test Node',
                'desc' => 'A test node',
                'testData' => ['key' => 'value'],
            ],
        ];

        $node = TestNode::fromArray($data);

        $this->assertEquals('node1', $node->getId());
        $this->assertEquals('Test Node', $node->getTitle());
        $this->assertEquals('A test node', $node->getDescription());
        $this->assertEquals(['x' => 50, 'y' => 100], $node->getPosition());
        $this->assertEquals(['key' => 'value'], $node->getTestData());
    }

    public function testFromArrayWithAllProperties(): void
    {
        $data = [
            'id' => 'complex_node',
            'type' => 'custom',
            'position' => ['x' => 100, 'y' => 200],
            'positionAbsolute' => ['x' => 150, 'y' => 250],
            'width' => 300,
            'height' => 150,
            'sourcePosition' => 'bottom',
            'targetPosition' => 'top',
            'selected' => true,
            'parentId' => 'parent_node',
            'extent' => 'parent',
            'zIndex' => 10,
            'selectable' => false,
            'draggable' => true,
            'data' => [
                'title' => 'Complex Node',
                'desc' => 'A complex test node',
            ],
        ];

        $node = TestNode::fromArray($data);

        $this->assertEquals('complex_node', $node->getId());
        $this->assertEquals('Complex Node', $node->getTitle());
        $this->assertEquals('A complex test node', $node->getDescription());
        $this->assertEquals(['x' => 100, 'y' => 200], $node->getPosition());
    }

    public function testFromArrayWithEmptyData(): void
    {
        $data = [];

        $node = TestNode::fromArray($data);

        $this->assertEquals('', $node->getId());
        $this->assertEquals('', $node->getTitle());
        $this->assertEquals('', $node->getDescription());
        $this->assertEquals(['x' => 0, 'y' => 0], $node->getPosition());
    }

    public function testToArrayBasic(): void
    {
        $node = new TestNode('test_id', 'Test Title', 'Test Description');
        $node->setPosition(50, 100);

        $array = $node->toArray();

        $this->assertEquals([
            'id' => 'test_id',
            'type' => 'custom',
            'position' => ['x' => 50, 'y' => 100],
            'data' => [
                'type' => 'test',
                'title' => 'Test Title',
                'desc' => 'Test Description',
                'selected' => false,
            ],
        ], $array);
    }

    public function testToArrayWithTestData(): void
    {
        $node = new TestNode('test_with_data');
        $node->setTestData(['custom' => 'data', 'number' => 42]);

        $array = $node->toArray();

        $this->assertIsArray($array['data']);
        $nodeData = $array['data'];
        $this->assertIsArray($nodeData['testData']);
        $this->assertEquals(['custom' => 'data', 'number' => 42], $nodeData['testData']);
    }

    public function testToArrayWithOptionalProperties(): void
    {
        $node = new TestNode('full_node');
        $node->setPosition(100, 200);
        $node->setSelected(true);
        $node->setParentId('parent');
        $node->setExtent('parent');

        $array = $node->toArray();

        $this->assertEquals('full_node', $array['id']);
        $this->assertEquals(['x' => 100, 'y' => 200], $array['position']);
        $this->assertTrue($array['selected']);
        $this->assertEquals('parent', $array['parentId']);
        $this->assertEquals('parent', $array['extent']);
    }

    public function testToArrayExcludesDefaults(): void
    {
        $node = new TestNode('minimal_node');

        $array = $node->toArray();

        // 默认值不应该出现在输出中
        $this->assertArrayNotHasKey('positionAbsolute', $array);
        $this->assertArrayNotHasKey('width', $array);
        $this->assertArrayNotHasKey('height', $array);
        $this->assertArrayNotHasKey('sourcePosition', $array);
        $this->assertArrayNotHasKey('targetPosition', $array);
        $this->assertArrayNotHasKey('selected', $array);
        $this->assertArrayNotHasKey('parentId', $array);
        $this->assertArrayNotHasKey('extent', $array);
        $this->assertArrayNotHasKey('zIndex', $array);
        $this->assertArrayNotHasKey('selectable', $array);
        $this->assertArrayNotHasKey('draggable', $array);
    }

    public function testGetNodeType(): void
    {
        $node = new TestNode('test');

        $this->assertEquals('test', $node->getNodeType());
    }

    public function testSetterMethods(): void
    {
        $node = new TestNode('setter_test');

        $node->setTitle('Setter Title');
        $node->setDescription('Setter Description');
        $node->setPosition(50, 75);
        $node->setSelected(true);
        $node->setParentId('parent');
        $node->setExtent('parent');

        $this->assertEquals('Setter Title', $node->getTitle());
        $this->assertEquals('Setter Description', $node->getDescription());
        $this->assertEquals(['x' => 50, 'y' => 75], $node->getPosition());
    }

    public function testBasePropertiesSetCorrectly(): void
    {
        $data = [
            'id' => 'properties_test',
            'position' => ['x' => 300, 'y' => 400],
            'positionAbsolute' => ['x' => 350, 'y' => 450],
            'width' => 200,
            'height' => 100,
            'sourcePosition' => 'top',
            'targetPosition' => 'bottom',
            'selected' => true,
            'parentId' => 'container',
            'extent' => 'parent',
            'zIndex' => 5,
            'selectable' => false,
            'draggable' => false,
            'data' => [
                'title' => 'Properties Test',
                'desc' => 'Testing all properties',
            ],
        ];

        $node = TestNode::fromArray($data);
        $array = $node->toArray();

        $this->assertEquals(['x' => 300, 'y' => 400], $array['position']);
        $this->assertEquals(['x' => 350, 'y' => 450], $array['positionAbsolute']);
        $this->assertEquals(200, $array['width']);
        $this->assertEquals(100, $array['height']);
        $this->assertEquals('top', $array['sourcePosition']);
        $this->assertEquals('bottom', $array['targetPosition']);
        $this->assertTrue($array['selected']);
        $this->assertEquals('container', $array['parentId']);
        $this->assertEquals('parent', $array['extent']);
        $this->assertEquals(5, $array['zIndex']);
        $this->assertFalse($array['selectable']);
        $this->assertFalse($array['draggable']);
    }

    public function testNodeDataStructure(): void
    {
        $node = new TestNode('data_test', 'Data Title', 'Data Description');
        $node->setSelected(true);

        $array = $node->toArray();
        $this->assertIsArray($array['data']);
        $nodeData = $array['data'];

        $this->assertIsString($nodeData['type']);
        $this->assertIsString($nodeData['title']);
        $this->assertIsString($nodeData['desc']);
        $this->assertIsBool($nodeData['selected']);
        $this->assertEquals('test', $nodeData['type']);
        $this->assertEquals('Data Title', $nodeData['title']);
        $this->assertEquals('Data Description', $nodeData['desc']);
        $this->assertTrue($nodeData['selected']);
    }

    public function testRoundTripSerialization(): void
    {
        $originalData = [
            'id' => 'roundtrip_test',
            'type' => 'custom',
            'position' => ['x' => 150, 'y' => 250],
            'width' => 300,
            'height' => 200,
            'selected' => true,
            'data' => [
                'title' => 'Round Trip',
                'desc' => 'Testing serialization',
                'testData' => ['important' => 'value'],
            ],
        ];

        $node = TestNode::fromArray($originalData);
        $serialized = $node->toArray();

        // 核心属性应该保持一致
        $this->assertEquals($originalData['id'], $serialized['id']);
        $this->assertEquals($originalData['position'], $serialized['position']);
        $this->assertEquals($originalData['width'], $serialized['width']);
        $this->assertEquals($originalData['height'], $serialized['height']);
        $this->assertEquals($originalData['selected'], $serialized['selected']);
        $this->assertIsArray($serialized['data']);
        $this->assertIsString($serialized['data']['title']);
        $this->assertIsString($serialized['data']['desc']);
        $this->assertIsArray($serialized['data']['testData']);
        $this->assertEquals($originalData['data']['title'], $serialized['data']['title']);
        $this->assertEquals($originalData['data']['desc'], $serialized['data']['desc']);
        $this->assertEquals($originalData['data']['testData'], $serialized['data']['testData']);
    }
}
