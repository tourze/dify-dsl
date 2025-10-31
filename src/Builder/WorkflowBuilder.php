<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Builder;

use Tourze\DifyDsl\Core\App;
use Tourze\DifyDsl\Core\Graph;
use Tourze\DifyDsl\Core\Variable;
use Tourze\DifyDsl\Core\Workflow;
use Tourze\DifyDsl\Nodes\AbstractNode;
use Tourze\DifyDsl\Nodes\AnswerNode;
use Tourze\DifyDsl\Nodes\CodeNode;
use Tourze\DifyDsl\Nodes\EndNode;
use Tourze\DifyDsl\Nodes\LLMNode;
use Tourze\DifyDsl\Nodes\StartNode;
use Tourze\DifyDsl\Nodes\ToolNode;

/**
 * 工作流构建器 - 提供流式 API 构建工作流
 */
class WorkflowBuilder
{
    private string $name = '';

    private string $description = '';

    private string $mode = 'workflow';

    private string $icon = '🤖';

    private string $iconBackground = '#FFEAD5';

    private Graph $graph;

    /** @var Variable[] */
    private array $environmentVariables = [];

    /** @var Variable[] */
    private array $conversationVariables = [];

    /** @var array<string, mixed> */
    private array $features = [];

    /** @var array<string, mixed> */
    private array $dependencies = [];

    /** @var array<string, mixed>|null */
    private ?array $modelConfig = null;

    private ?AbstractNode $lastNode = null;

    public function __construct()
    {
        $this->graph = new Graph();
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    public function setIcon(string $icon, string $background = '#FFEAD5'): void
    {
        $this->icon = $icon;
        $this->iconBackground = $background;
    }

    public function addEnvironmentVariable(string $name, string $type = 'string', mixed $default = null): self
    {
        $this->environmentVariables[] = new Variable(
            variable: $name,
            label: $name,
            type: $type,
            defaultValue: $default
        );

        return $this;
    }

    public function addConversationVariable(string $name, string $type = 'string', mixed $default = null): self
    {
        $this->conversationVariables[] = new Variable(
            variable: $name,
            label: $name,
            type: $type,
            defaultValue: $default
        );

        return $this;
    }

    /**
     * @param string[] $allowedTypes
     */
    public function enableFileUpload(array $allowedTypes = ['image'], int $numberLimits = 5): self
    {
        $this->features['file_upload'] = [
            'enabled' => true,
            'allowed_file_types' => $allowedTypes,
            'number_limits' => $numberLimits,
        ];

        return $this;
    }

    /**
     * @phpstan-ignore-next-line symplify.noReturnSetterMethod
     */
    public function setOpeningStatement(string $statement): self
    {
        $this->features['opening_statement'] = $statement;

        return $this;
    }

    public function addStartNode(?callable $configurator = null): self
    {
        $node = StartNode::create('start');

        if (null !== $configurator) {
            $configurator($node);
        }

        $this->graph->addNode($node);
        $this->lastNode = $node;

        return $this;
    }

    public static function create(): self
    {
        return new self();
    }

    public function addLLMNode(?string $id = null, ?callable $configurator = null): self
    {
        $node = LLMNode::create($id ?? $this->generateNodeId('llm'));

        if (null !== $configurator) {
            $configurator($node);
        }

        $this->graph->addNode($node);

        if (null !== $this->lastNode) {
            $this->graph->connectNodes($this->lastNode->getId(), $node->getId());
        }

        $this->lastNode = $node;

        return $this;
    }

    private function generateNodeId(string $type): string
    {
        return $type . '_' . time() . '_' . mt_rand(1000, 9999);
    }

    public function connectNodes(string $sourceId, string $targetId): self
    {
        $this->graph->connectNodes($sourceId, $targetId);

        return $this;
    }

    public function addToolNode(?string $id = null, ?callable $configurator = null): self
    {
        $node = ToolNode::create($id ?? $this->generateNodeId('tool'));

        if (null !== $configurator) {
            $configurator($node);
        }

        $this->graph->addNode($node);

        if (null !== $this->lastNode) {
            $this->graph->connectNodes($this->lastNode->getId(), $node->getId());
        }

        $this->lastNode = $node;

        return $this;
    }

    public function addCodeNode(?string $id = null, ?callable $configurator = null): self
    {
        $node = CodeNode::create($id ?? $this->generateNodeId('code'));

        if (null !== $configurator) {
            $configurator($node);
        }

        $this->graph->addNode($node);

        if (null !== $this->lastNode) {
            $this->graph->connectNodes($this->lastNode->getId(), $node->getId());
        }

        $this->lastNode = $node;

        return $this;
    }

    public function addEndNode(?callable $configurator = null): self
    {
        $node = EndNode::create('end');

        if (null !== $configurator) {
            $configurator($node);
        }

        $this->graph->addNode($node);

        if (null !== $this->lastNode) {
            $this->graph->connectNodes($this->lastNode->getId(), $node->getId());
        }

        $this->lastNode = $node;

        return $this;
    }

    public function addAnswerNode(?string $id = null, ?callable $configurator = null): self
    {
        $node = AnswerNode::create($id ?? $this->generateNodeId('answer'));

        if (null !== $configurator) {
            $configurator($node);
        }

        $this->graph->addNode($node);

        if (null !== $this->lastNode) {
            $this->graph->connectNodes($this->lastNode->getId(), $node->getId());
        }

        $this->lastNode = $node;

        return $this;
    }

    public function addCustomNode(AbstractNode $node): self
    {
        $this->graph->addNode($node);

        if (null !== $this->lastNode) {
            $this->graph->connectNodes($this->lastNode->getId(), $node->getId());
        }

        $this->lastNode = $node;

        return $this;
    }

    /**
     * @param array<string, mixed>|null $modelConfig
     */
    public function setModelConfig(?array $modelConfig): void
    {
        $this->modelConfig = $modelConfig;
    }

    public function build(): App
    {
        $workflow = new Workflow(
            graph: $this->graph,
            environmentVariables: $this->environmentVariables,
            conversationVariables: $this->conversationVariables,
            features: $this->features
        );

        return new App(
            name: $this->name,
            description: $this->description,
            mode: $this->mode,
            workflow: $workflow,
            icon: $this->icon,
            iconBackground: $this->iconBackground,
            dependencies: $this->dependencies,
            modelConfig: $this->modelConfig
        );
    }
}
