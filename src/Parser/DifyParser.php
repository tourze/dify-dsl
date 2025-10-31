<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Parser;

use Symfony\Component\Yaml\Yaml;
use Tourze\DifyDsl\Core\App;
use Tourze\DifyDsl\Core\Edge;
use Tourze\DifyDsl\Core\Graph;
use Tourze\DifyDsl\Core\Workflow;
use Tourze\DifyDsl\Exception\ParseException;
use Tourze\DifyDsl\Nodes\AbstractNode;

/**
 * Dify DSL 解析器
 */
class DifyParser
{
    public function __construct()
    {
    }

    /**
     * 从文件解析 Dify DSL
     */
    public function parseFile(string $filePath): App
    {
        if (!file_exists($filePath)) {
            throw new ParseException("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if (false === $content) {
            throw new ParseException("Failed to read file: {$filePath}");
        }

        return $this->parse($content);
    }

    /**
     * 从 YAML 字符串解析 Dify DSL
     */
    public function parse(string $yamlContent): App
    {
        try {
            $data = Yaml::parse($yamlContent);
        } catch (\Exception $e) {
            throw new ParseException('Failed to parse YAML: ' . $e->getMessage(), 0, $e);
        }

        if (!is_array($data)) {
            throw new ParseException('Parsed YAML must be an array');
        }

        /** @var array<string, mixed> $data */
        return $this->parseFromArray($data);
    }

    /**
     * 从数组解析 Dify DSL
     *
     * @param array<string, mixed> $data
     */
    public function parseFromArray(array $data): App
    {
        $this->validateStructure($data);

        // 解析 workflow
        if (!isset($data['workflow']) || !is_array($data['workflow'])) {
            throw new ParseException('Workflow data must be an array');
        }

        /** @var array<string, mixed> $workflowData */
        $workflowData = $data['workflow'];
        $workflow = $this->parseWorkflow($workflowData);

        // 创建 App 实例
        $app = App::fromArray($data);
        $app->setWorkflow($workflow);

        return $app;
    }

    /**
     * 验证数据结构
     *
     * @param array<string, mixed> $data
     */
    private function validateStructure(array $data): void
    {
        $this->validateRequiredKeys($data);
        $this->validateKind($data);
        $this->validateVersion($data);
        $this->validateAppData($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateRequiredKeys(array $data): void
    {
        $requiredKeys = ['app', 'kind', 'version', 'workflow'];
        foreach ($requiredKeys as $key) {
            if (!isset($data[$key])) {
                throw new ParseException("Missing required key: {$key}");
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateKind(array $data): void
    {
        if (!is_string($data['kind'])) {
            throw new ParseException('Kind must be a string');
        }

        if ('app' !== $data['kind']) {
            throw new ParseException("Invalid kind: expected 'app', got '{$data['kind']}'");
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateVersion(array $data): void
    {
        if (!is_string($data['version'])) {
            throw new ParseException('Version must be a string');
        }

        $supportedVersions = ['0.1.5', '0.2.0', '0.3.0'];
        if (!in_array($data['version'], $supportedVersions, true)) {
            throw new ParseException("Unsupported version: {$data['version']}");
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateAppData(array $data): void
    {
        if (!is_array($data['app'])) {
            throw new ParseException('App data must be an array');
        }

        /** @var array<string, mixed> $appData */
        $appData = $data['app'];
        $requiredAppKeys = ['name', 'mode'];

        foreach ($requiredAppKeys as $key) {
            if (!isset($appData[$key])) {
                throw new ParseException("Missing required app key: {$key}");
            }
        }

        if (!is_string($appData['mode'])) {
            throw new ParseException('Mode must be a string');
        }

        $supportedModes = ['workflow', 'chat', 'advanced-chat', 'agent-chat'];
        if (!in_array($appData['mode'], $supportedModes, true)) {
            throw new ParseException("Unsupported mode: {$appData['mode']}");
        }
    }

    /**
     * 解析工作流数据
     *
     * @param array<string, mixed> $data
     */
    private function parseWorkflow(array $data): Workflow
    {
        // 解析图结构
        $graphData = isset($data['graph']) && is_array($data['graph']) ? $data['graph'] : [];
        /** @var array<string, mixed> $graphData */
        $graph = $this->parseGraph($graphData);

        $workflow = Workflow::fromArray($data);
        $workflow->setGraph($graph);

        return $workflow;
    }

    /**
     * 解析图数据
     *
     * @param array<string, mixed> $data
     */
    private function parseGraph(array $data): Graph
    {
        /** @var array<int, array<string, mixed>> $nodesData */
        $nodesData = isset($data['nodes']) && is_array($data['nodes']) ? $data['nodes'] : [];
        $nodes = $this->parseNodes($nodesData);

        /** @var array<int, array<string, mixed>> $edgesData */
        $edgesData = isset($data['edges']) && is_array($data['edges']) ? $data['edges'] : [];
        $edges = $this->parseEdges($edgesData);

        return new Graph($nodes, $edges);
    }

    /**
     * 解析节点数据
     *
     * @param array<int, array<string, mixed>> $nodesData
     * @return array<int, AbstractNode>
     */
    private function parseNodes(array $nodesData): array
    {
        $nodes = [];

        foreach ($nodesData as $nodeData) {
            if (!is_array($nodeData)) {
                continue;
            }

            try {
                $node = NodeFactory::createFromArray($nodeData);
                $nodes[] = $node;
            } catch (\Exception|\Error $e) {
                $nodeId = isset($nodeData['id']) && is_string($nodeData['id']) ? $nodeData['id'] : 'unknown';
                throw new ParseException('Failed to parse node ' . $nodeId . ': ' . $e->getMessage(), 0, $e);
            }
        }

        return $nodes;
    }

    /**
     * 解析边数据
     *
     * @param array<int, array<string, mixed>> $edgesData
     * @return array<int, Edge>
     */
    private function parseEdges(array $edgesData): array
    {
        $edges = [];

        foreach ($edgesData as $edgeData) {
            if (!is_array($edgeData)) {
                throw new ParseException('Failed to parse edge: expected array, got ' . gettype($edgeData));
            }

            try {
                $edge = Edge::fromArray($edgeData);
                $edges[] = $edge;
            } catch (\Exception|\Error $e) {
                $edgeId = isset($edgeData['id']) && is_string($edgeData['id']) ? $edgeData['id'] : 'unknown';
                throw new ParseException('Failed to parse edge ' . $edgeId . ': ' . $e->getMessage(), 0, $e);
            }
        }

        return $edges;
    }
}
