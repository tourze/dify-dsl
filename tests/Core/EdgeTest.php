<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Core;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyDsl\Core\Edge;

/**
 * @internal
 */
#[CoversClass(Edge::class)]
class EdgeTest extends TestCase
{
    public function testCreateEdge(): void
    {
        $edge = new Edge(
            id: 'edge1',
            source: 'node1',
            target: 'node2',
            type: 'custom',
            sourceHandle: 'out1',
            targetHandle: 'in1',
            selected: true,
            data: ['key' => 'value'],
            zIndex: 10
        );

        $this->assertEquals('edge1', $edge->getId());
        $this->assertEquals('node1', $edge->getSource());
        $this->assertEquals('node2', $edge->getTarget());
        $this->assertEquals('custom', $edge->getType());
        $this->assertEquals('out1', $edge->getSourceHandle());
        $this->assertEquals('in1', $edge->getTargetHandle());
        $this->assertTrue($edge->isSelected());
        $this->assertEquals(['key' => 'value'], $edge->getData());
        $this->assertEquals(10, $edge->getZIndex());
    }

    public function testCreateEdgeWithDefaults(): void
    {
        $edge = new Edge(
            id: 'simple_edge',
            source: 'start',
            target: 'end'
        );

        $this->assertEquals('simple_edge', $edge->getId());
        $this->assertEquals('start', $edge->getSource());
        $this->assertEquals('end', $edge->getTarget());
        $this->assertEquals('custom', $edge->getType());
        $this->assertNull($edge->getSourceHandle());
        $this->assertNull($edge->getTargetHandle());
        $this->assertFalse($edge->isSelected());
        $this->assertEquals([], $edge->getData());
        $this->assertEquals(0, $edge->getZIndex());
    }

    public function testCreateFactoryMethod(): void
    {
        $edge = Edge::create('node_a', 'node_b');

        $this->assertEquals('node_a-node_b', $edge->getId());
        $this->assertEquals('node_a', $edge->getSource());
        $this->assertEquals('node_b', $edge->getTarget());
        $this->assertEquals('custom', $edge->getType());
    }

    public function testCreateFactoryMethodWithCustomId(): void
    {
        $edge = Edge::create('source_node', 'target_node', 'custom_edge_id');

        $this->assertEquals('custom_edge_id', $edge->getId());
        $this->assertEquals('source_node', $edge->getSource());
        $this->assertEquals('target_node', $edge->getTarget());
    }

    public function testFromArray(): void
    {
        $data = [
            'id' => 'test_edge',
            'source' => 'from_node',
            'target' => 'to_node',
            'type' => 'bezier',
            'sourceHandle' => 'output',
            'targetHandle' => 'input',
            'selected' => true,
            'data' => ['color' => 'blue', 'weight' => 5],
            'zIndex' => 15,
        ];

        $edge = Edge::fromArray($data);

        $this->assertEquals('test_edge', $edge->getId());
        $this->assertEquals('from_node', $edge->getSource());
        $this->assertEquals('to_node', $edge->getTarget());
        $this->assertEquals('bezier', $edge->getType());
        $this->assertEquals('output', $edge->getSourceHandle());
        $this->assertEquals('input', $edge->getTargetHandle());
        $this->assertTrue($edge->isSelected());
        $this->assertEquals(['color' => 'blue', 'weight' => 5], $edge->getData());
        $this->assertEquals(15, $edge->getZIndex());
    }

    public function testFromArrayWithDefaults(): void
    {
        $data = [
            'id' => 'minimal_edge',
            'source' => 'start',
            'target' => 'end',
        ];

        $edge = Edge::fromArray($data);

        $this->assertEquals('minimal_edge', $edge->getId());
        $this->assertEquals('start', $edge->getSource());
        $this->assertEquals('end', $edge->getTarget());
        $this->assertEquals('custom', $edge->getType());
        $this->assertNull($edge->getSourceHandle());
        $this->assertNull($edge->getTargetHandle());
        $this->assertFalse($edge->isSelected());
        $this->assertEquals([], $edge->getData());
        $this->assertEquals(0, $edge->getZIndex());
    }

    public function testFromArrayWithEmptyData(): void
    {
        $data = [];

        $edge = Edge::fromArray($data);

        $this->assertEquals('', $edge->getId());
        $this->assertEquals('', $edge->getSource());
        $this->assertEquals('', $edge->getTarget());
        $this->assertEquals('custom', $edge->getType());
    }

    public function testToArray(): void
    {
        $edge = new Edge(
            id: 'full_edge',
            source: 'node1',
            target: 'node2',
            type: 'straight',
            sourceHandle: 'out',
            targetHandle: 'in',
            selected: true,
            data: ['style' => 'dashed'],
            zIndex: 5
        );

        $array = $edge->toArray();

        $this->assertEquals([
            'id' => 'full_edge',
            'type' => 'straight',
            'source' => 'node1',
            'target' => 'node2',
            'selected' => true,
            'sourceHandle' => 'out',
            'targetHandle' => 'in',
            'data' => ['style' => 'dashed'],
            'zIndex' => 5,
        ], $array);
    }

    public function testToArrayWithNullValues(): void
    {
        $edge = new Edge(
            id: 'simple_edge',
            source: 'start',
            target: 'end',
            selected: false
        );

        $array = $edge->toArray();

        $this->assertEquals([
            'id' => 'simple_edge',
            'type' => 'custom',
            'source' => 'start',
            'target' => 'end',
            'selected' => false,
        ], $array);

        // 确保 null 值和默认值不会出现在输出中
        $this->assertArrayNotHasKey('sourceHandle', $array);
        $this->assertArrayNotHasKey('targetHandle', $array);
        $this->assertArrayNotHasKey('data', $array);
        $this->assertArrayNotHasKey('zIndex', $array);
    }

    public function testToArrayWithEmptyData(): void
    {
        $edge = new Edge(
            id: 'edge_empty_data',
            source: 'a',
            target: 'b',
            data: []
        );

        $array = $edge->toArray();

        // 空数组不应该出现在输出中
        $this->assertArrayNotHasKey('data', $array);
    }

    public function testEdgeConnectivity(): void
    {
        $edge = Edge::create('workflow_start', 'llm_process');

        $this->assertEquals('workflow_start', $edge->getSource());
        $this->assertEquals('llm_process', $edge->getTarget());
        $this->assertEquals('workflow_start-llm_process', $edge->getId());
    }

    public function testEdgeWithHandles(): void
    {
        $edge = new Edge(
            id: 'conditional_edge',
            source: 'condition_node',
            target: 'action_node',
            sourceHandle: 'true_branch',
            targetHandle: 'input'
        );

        $this->assertEquals('true_branch', $edge->getSourceHandle());
        $this->assertEquals('input', $edge->getTargetHandle());
    }

    public function testEdgeMetadata(): void
    {
        $metadata = [
            'condition' => 'user_age > 18',
            'priority' => 'high',
            'color' => '#FF0000',
        ];

        $edge = new Edge(
            id: 'metadata_edge',
            source: 'check',
            target: 'action',
            data: $metadata
        );

        $this->assertEquals($metadata, $edge->getData());
        $this->assertEquals('user_age > 18', $edge->getData()['condition']);
    }
}
