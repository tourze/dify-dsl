<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Nodes;

use Tourze\DifyDsl\Core\Variable;

/**
 * 开始节点 - 定义工作流的输入变量
 */
class StartNode extends AbstractNode
{
    /** @var Variable[] */
    private array $variables = [];

    public function __construct(string $id, string $title = '开始', string $description = '')
    {
        parent::__construct($id, $title, $description);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $nodeData = $data['data'] ?? [];
        if (!is_array($nodeData)) {
            $nodeData = [];
        }
        /** @var array<string, mixed> $nodeData */

        /** @phpstan-ignore-next-line */
        $node = new static(
            id: is_string($data['id'] ?? null) ? $data['id'] : '',
            title: is_string($nodeData['title'] ?? null) ? $nodeData['title'] : '开始',
            description: is_string($nodeData['desc'] ?? null) ? $nodeData['desc'] : ''
        );

        self::setBaseProperties($node, $data);
        self::loadVariables($node, $nodeData);

        return $node;
    }

    /**
     * @param array<string, mixed> $nodeData
     */
    private static function loadVariables(self $node, array $nodeData): void
    {
        if (isset($nodeData['variables']) && is_array($nodeData['variables'])) {
            foreach ($nodeData['variables'] as $varData) {
                if (is_array($varData)) {
                    /** @var array<string, mixed> $varData */
                    $node->addVariable(Variable::fromArray($varData));
                }
            }
        }
    }

    public function addVariable(Variable $variable): self
    {
        $this->variables[] = $variable;

        return $this;
    }

    public static function create(string $id = 'start'): self
    {
        return new self($id);
    }

    public function getNodeType(): string
    {
        return 'start';
    }

    /**
     * @return Variable[]
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * @param Variable[] $variables
     */
    public function setVariables(array $variables): void
    {
        $this->variables = $variables;
    }

    public function addVariableFromArray(string $variable, string $type, bool $required = false, ?string $label = null): self
    {
        $this->variables[] = new Variable(
            variable: $variable,
            label: $label ?? $variable,
            type: $type,
            required: $required
        );

        return $this;
    }

    protected function getNodeData(): array
    {
        $data = parent::getNodeData();

        if ([] !== $this->variables) {
            $data['variables'] = array_map(
                fn (Variable $var) => $var->toArray(),
                $this->variables
            );
        }

        return $data;
    }
}
