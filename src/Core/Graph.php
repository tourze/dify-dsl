<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Core;

use Tourze\DifyDsl\Nodes\AbstractNode;

/**
 * 表示 Dify DSL 工作流图结构
 */
class Graph
{
    /** @var AbstractNode[] */
    private array $nodes = [];

    /** @var Edge[] */
    private array $edges = [];

    /**
     * @param AbstractNode[] $nodes
     * @param Edge[] $edges
     */
    public function __construct(array $nodes = [], array $edges = [])
    {
        foreach ($nodes as $node) {
            $this->addNode($node);
        }

        foreach ($edges as $edge) {
            $this->addEdge($edge);
        }
    }

    public function addNode(AbstractNode $node): self
    {
        $this->nodes[$node->getId()] = $node;

        return $this;
    }

    public function addEdge(Edge $edge): self
    {
        $this->edges[$edge->getId()] = $edge;

        return $this;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $nodes = [];
        $edges = [];

        // 先处理边,节点将在 Parser 中通过工厂创建
        if (isset($data['edges']) && is_array($data['edges'])) {
            foreach ($data['edges'] as $edgeData) {
                if (!is_array($edgeData)) {
                    continue;
                }
                /** @var array<string, mixed> $edgeData */
                $edges[] = Edge::fromArray($edgeData);
            }
        }

        return new self($nodes, $edges);
    }

    public function removeNode(string $id): self
    {
        unset($this->nodes[$id]);

        // 删除相关的边
        $this->edges = array_filter(
            $this->edges,
            fn (Edge $edge) => $edge->getSource() !== $id && $edge->getTarget() !== $id
        );

        return $this;
    }

    public function getNode(string $id): ?AbstractNode
    {
        return $this->nodes[$id] ?? null;
    }

    /**
     * @return array<string, AbstractNode>
     */
    public function getNodesIndexedById(): array
    {
        /** @var array<string, AbstractNode> */
        return $this->nodes;
    }

    public function removeEdge(string $id): self
    {
        unset($this->edges[$id]);

        return $this;
    }

    public function getEdge(string $id): ?Edge
    {
        return $this->edges[$id] ?? null;
    }

    /**
     * @return array<string, Edge>
     */
    public function getEdgesIndexedById(): array
    {
        /** @var array<string, Edge> */
        return $this->edges;
    }

    public function connectNodes(string $sourceId, string $targetId): self
    {
        $edge = Edge::create($sourceId, $targetId);

        return $this->addEdge($edge);
    }

    /**
     * @return Edge[]
     */
    public function getIncomingEdges(string $nodeId): array
    {
        return array_filter(
            $this->edges,
            fn (Edge $edge) => $edge->getTarget() === $nodeId
        );
    }

    /**
     * @return Edge[]
     */
    public function getOutgoingEdges(string $nodeId): array
    {
        return array_filter(
            $this->edges,
            fn (Edge $edge) => $edge->getSource() === $nodeId
        );
    }

    /**
     * @return string[]
     */
    public function validate(): array
    {
        $errors = [];

        // 检查是否有开始节点
        $startNodes = $this->getStartNodes();
        if ([] === $startNodes) {
            $errors[] = 'Graph must have at least one start node';
        }

        // 检查是否有结束节点
        $endNodes = $this->getEndNodes();
        if ([] === $endNodes) {
            $errors[] = 'Graph must have at least one end or answer node';
        }

        // 检查边的有效性
        foreach ($this->edges as $edge) {
            if (!isset($this->nodes[$edge->getSource()])) {
                $errors[] = sprintf('Edge %s references non-existent source node %s', $edge->getId(), $edge->getSource());
            }
            if (!isset($this->nodes[$edge->getTarget()])) {
                $errors[] = sprintf('Edge %s references non-existent target node %s', $edge->getId(), $edge->getTarget());
            }
        }

        return $errors;
    }

    /**
     * @return AbstractNode[]
     */
    public function getStartNodes(): array
    {
        return array_filter(
            $this->nodes,
            fn (AbstractNode $node) => 'start' === $node->getNodeType()
        );
    }

    /**
     * @return AbstractNode[]
     */
    public function getEndNodes(): array
    {
        return array_filter(
            $this->nodes,
            fn (AbstractNode $node) => in_array($node->getNodeType(), ['end', 'answer'], true)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'nodes' => array_map(fn (AbstractNode $node) => $node->toArray(), $this->getNodes()),
            'edges' => array_map(fn (Edge $edge) => $edge->toArray(), $this->getEdges()),
        ];
    }

    /**
     * @return AbstractNode[]
     */
    public function getNodes(): array
    {
        return array_values($this->nodes);
    }

    /** @return Edge[] */
    public function getEdges(): array
    {
        return array_values($this->edges);
    }
}
