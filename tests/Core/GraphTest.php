<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Core;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyDsl\Core\Edge;
use Tourze\DifyDsl\Core\Graph;
use Tourze\DifyDsl\Nodes\AbstractNode;
use Tourze\DifyDsl\Nodes\EndNode;
use Tourze\DifyDsl\Nodes\LLMNode;
use Tourze\DifyDsl\Nodes\StartNode;

/**
 * @internal
 */
#[CoversClass(Graph::class)]
class GraphTest extends TestCase
{
    public function testCreateEmptyGraph(): void
    {
        $graph = new Graph();

        $this->assertEmpty($graph->getNodes());
        $this->assertEmpty($graph->getEdges());
        $this->assertEmpty($graph->getNodesIndexedById());
        $this->assertEmpty($graph->getEdgesIndexedById());
    }

    public function testCreateGraphWithNodesAndEdges(): void
    {
        $startNode = new StartNode('start', 'Start Node', 'Start description');
        $startNode->setPosition(100, 200);
        $endNode = new EndNode('end', 'End Node', 'End description');
        $endNode->setPosition(300, 400);
        $edge = Edge::create('start', 'end');

        $graph = new Graph([$startNode, $endNode], [$edge]);

        $this->assertCount(2, $graph->getNodes());
        $this->assertCount(1, $graph->getEdges());
        $this->assertArrayHasKey('start', $graph->getNodesIndexedById());
        $this->assertArrayHasKey('end', $graph->getNodesIndexedById());
        $this->assertArrayHasKey('start-end', $graph->getEdgesIndexedById());
    }

    public function testAddNode(): void
    {
        $graph = new Graph();
        $node = new StartNode('test_start', 'Test Start', 'Test description');
        $node->setPosition(50, 50);

        $result = $graph->addNode($node);

        $this->assertSame($graph, $result); // 测试流式接口
        $this->assertCount(1, $graph->getNodes());
        $this->assertEquals('test_start', $graph->getNode('test_start')?->getId());
    }

    public function testAddEdge(): void
    {
        $graph = new Graph();
        $edge = Edge::create('source', 'target', 'test_edge');

        $result = $graph->addEdge($edge);

        $this->assertSame($graph, $result); // 测试流式接口
        $this->assertCount(1, $graph->getEdges());
        $this->assertEquals('test_edge', $graph->getEdge('test_edge')?->getId());
    }

    public function testRemoveNode(): void
    {
        $startNode = new StartNode('start', 'Start', 'Start node');
        $startNode->setPosition(0, 0);
        $middleNode = new LLMNode('middle', 'Middle', 'Middle node');
        $middleNode->setPosition(100, 100);
        $endNode = new EndNode('end', 'End', 'End node');
        $endNode->setPosition(200, 200);

        $edge1 = Edge::create('start', 'middle');
        $edge2 = Edge::create('middle', 'end');

        $graph = new Graph([$startNode, $middleNode, $endNode], [$edge1, $edge2]);

        $result = $graph->removeNode('middle');

        $this->assertSame($graph, $result); // 测试流式接口
        $this->assertCount(2, $graph->getNodes());
        $this->assertNull($graph->getNode('middle'));

        // 相关的边应该被删除
        $this->assertEmpty($graph->getEdges());
        $this->assertNull($graph->getEdge('start-middle'));
        $this->assertNull($graph->getEdge('middle-end'));
    }

    public function testRemoveEdge(): void
    {
        $edge = Edge::create('a', 'b', 'test_edge');
        $graph = new Graph([], [$edge]);

        $result = $graph->removeEdge('test_edge');

        $this->assertSame($graph, $result); // 测试流式接口
        $this->assertEmpty($graph->getEdges());
        $this->assertNull($graph->getEdge('test_edge'));
    }

    public function testConnectNodes(): void
    {
        $graph = new Graph();

        $result = $graph->connectNodes('node1', 'node2');

        $this->assertSame($graph, $result); // 测试流式接口
        $this->assertCount(1, $graph->getEdges());

        $edge = $graph->getEdge('node1-node2');
        $this->assertNotNull($edge);
        $this->assertEquals('node1', $edge->getSource());
        $this->assertEquals('node2', $edge->getTarget());
    }

    public function testGetIncomingEdges(): void
    {
        $edge1 = Edge::create('a', 'target');
        $edge2 = Edge::create('b', 'target');
        $edge3 = Edge::create('target', 'c');

        $graph = new Graph([], [$edge1, $edge2, $edge3]);

        $incomingEdges = $graph->getIncomingEdges('target');

        $this->assertCount(2, $incomingEdges);
        $this->assertContains($edge1, $incomingEdges);
        $this->assertContains($edge2, $incomingEdges);
        $this->assertNotContains($edge3, $incomingEdges);
    }

    public function testGetOutgoingEdges(): void
    {
        $edge1 = Edge::create('source', 'a');
        $edge2 = Edge::create('source', 'b');
        $edge3 = Edge::create('c', 'source');

        $graph = new Graph([], [$edge1, $edge2, $edge3]);

        $outgoingEdges = $graph->getOutgoingEdges('source');

        $this->assertCount(2, $outgoingEdges);
        $this->assertContains($edge1, $outgoingEdges);
        $this->assertContains($edge2, $outgoingEdges);
        $this->assertNotContains($edge3, $outgoingEdges);
    }

    public function testGetStartNodes(): void
    {
        $startNode1 = new StartNode('start1', 'Start 1', 'First start');
        $startNode1->setPosition(0, 0);
        $startNode2 = new StartNode('start2', 'Start 2', 'Second start');
        $startNode2->setPosition(0, 100);
        $endNode = new EndNode('end', 'End', 'End node');
        $endNode->setPosition(100, 0);

        $graph = new Graph([$startNode1, $startNode2, $endNode]);

        $startNodes = $graph->getStartNodes();

        $this->assertCount(2, $startNodes);
        $this->assertContains($startNode1, $startNodes);
        $this->assertContains($startNode2, $startNodes);
        $this->assertNotContains($endNode, $startNodes);
    }

    public function testGetEndNodes(): void
    {
        $startNode = new StartNode('start', 'Start', 'Start node');
        $startNode->setPosition(0, 0);
        $endNode = new EndNode('end', 'End', 'End node');
        $endNode->setPosition(100, 0);
        $llmNode = new LLMNode('llm', 'LLM', 'LLM node');
        $llmNode->setPosition(50, 50);

        $graph = new Graph([$startNode, $endNode, $llmNode]);

        $endNodes = $graph->getEndNodes();

        $this->assertCount(1, $endNodes);
        $this->assertContains($endNode, $endNodes);
        $this->assertNotContains($startNode, $endNodes);
        $this->assertNotContains($llmNode, $endNodes);
    }

    public function testValidateValidGraph(): void
    {
        $startNode = new StartNode('start', 'Start', 'Start node');
        $endNode = new EndNode('end', 'End', 'End node');
        $edge = Edge::create('start', 'end');

        $graph = new Graph([$startNode, $endNode], [$edge]);

        $errors = $graph->validate();

        $this->assertEmpty($errors);
    }

    public function testValidateGraphWithoutStartNode(): void
    {
        $endNode = new EndNode('end', 'End', 'End node');
        $graph = new Graph([$endNode]);

        $errors = $graph->validate();

        $this->assertContains('Graph must have at least one start node', $errors);
    }

    public function testValidateGraphWithoutEndNode(): void
    {
        $startNode = new StartNode('start', 'Start', 'Start node');
        $graph = new Graph([$startNode]);

        $errors = $graph->validate();

        $this->assertContains('Graph must have at least one end or answer node', $errors);
    }

    public function testValidateGraphWithInvalidEdges(): void
    {
        $startNode = new StartNode('start', 'Start', 'Start node');
        $endNode = new EndNode('end', 'End', 'End node');
        $invalidEdge1 = Edge::create('nonexistent', 'end');
        $invalidEdge2 = Edge::create('start', 'missing');

        $graph = new Graph([$startNode, $endNode], [$invalidEdge1, $invalidEdge2]);

        $errors = $graph->validate();

        $this->assertCount(2, $errors);
        $this->assertStringContainsString('references non-existent source node nonexistent', $errors[0]);
        $this->assertStringContainsString('references non-existent target node missing', $errors[1]);
    }

    public function testFromArray(): void
    {
        $data = [
            'nodes' => [], // 节点将在 Parser 中处理
            'edges' => [
                [
                    'id' => 'edge1',
                    'source' => 'start',
                    'target' => 'end',
                ],
            ],
        ];

        $graph = Graph::fromArray($data);

        $this->assertEmpty($graph->getNodes());
        $this->assertCount(1, $graph->getEdges());
        $this->assertEquals('edge1', $graph->getEdge('edge1')?->getId());
    }

    public function testFromArrayWithEmptyData(): void
    {
        $graph = Graph::fromArray([]);

        $this->assertEmpty($graph->getNodes());
        $this->assertEmpty($graph->getEdges());
    }

    public function testToArray(): void
    {
        $startNode = new StartNode('start', 'Start', 'Start node');
        $endNode = new EndNode('end', 'End', 'End node');
        $edge = Edge::create('start', 'end');

        $graph = new Graph([$startNode, $endNode], [$edge]);

        $array = $graph->toArray();

        $this->assertArrayHasKey('nodes', $array);
        $this->assertArrayHasKey('edges', $array);
        $nodes = $array['nodes'] ?? [];
        $edges = $array['edges'] ?? [];
        $this->assertIsArray($nodes);
        $this->assertIsArray($edges);
        $this->assertCount(2, $nodes);
        $this->assertCount(1, $edges);
    }

    public function testNodeReplacement(): void
    {
        $node1 = new StartNode('test', 'Test', 'Test node');
        $node2 = new StartNode('test', 'Test', 'Test node'); // 相同 ID，不同位置
        $node2->setPosition(50, 0); // 设置不同的位置

        $graph = new Graph();
        $graph->addNode($node1);
        $graph->addNode($node2); // 应该替换第一个节点

        $this->assertCount(1, $graph->getNodes());
        $this->assertEquals(50, $graph->getNode('test')?->getPosition()['x']);
    }

    public function testEdgeReplacement(): void
    {
        $edge1 = new Edge('test_edge', 'a', 'b', 'custom');
        $edge2 = new Edge('test_edge', 'c', 'd', 'bezier'); // 相同 ID，不同属性

        $graph = new Graph();
        $graph->addEdge($edge1);
        $graph->addEdge($edge2); // 应该替换第一个边

        $this->assertCount(1, $graph->getEdges());
        $this->assertEquals('c', $graph->getEdge('test_edge')?->getSource());
        $this->assertEquals('bezier', $graph->getEdge('test_edge')?->getType());
    }
}
