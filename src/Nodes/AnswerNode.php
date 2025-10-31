<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Nodes;

/**
 * 回复节点 - 用于 Chatflow，向用户返回流式输出
 */
class AnswerNode extends AbstractNode
{
    private string $answer = '';

    /** @var array<string, mixed> */
    private array $variables = [];

    public function __construct(string $id, string $title = '直接回复', string $description = '')
    {
        parent::__construct($id, $title, $description);
    }

    /** @param array<string, mixed> $data */
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
            title: is_string($nodeData['title'] ?? null) ? $nodeData['title'] : '直接回复',
            description: is_string($nodeData['desc'] ?? null) ? $nodeData['desc'] : ''
        );

        self::setBaseProperties($node, $data);

        if (isset($nodeData['answer']) && is_string($nodeData['answer'])) {
            $node->setAnswer($nodeData['answer']);
        }

        if (isset($nodeData['variables']) && is_array($nodeData['variables'])) {
            /** @var array<string, mixed> $variables */
            $variables = $nodeData['variables'];
            $node->setVariables($variables);
        }

        return $node;
    }

    public static function create(string $id = 'answer', string $answer = ''): self
    {
        $node = new self($id);
        if ('' !== $answer) {
            $node->setAnswer($answer);
        }

        return $node;
    }

    public function getNodeType(): string
    {
        return 'answer';
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): void
    {
        $this->answer = $answer;
    }

    /**
     * @return array<string, mixed>
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function setVariables(array $variables): void
    {
        $this->variables = $variables;
    }

    protected function getNodeData(): array
    {
        $data = parent::getNodeData();

        if ('' !== $this->answer) {
            $data['answer'] = $this->answer;
        }

        if ([] !== $this->variables) {
            $data['variables'] = $this->variables;
        }

        return $data;
    }
}
