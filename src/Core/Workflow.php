<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Core;

/**
 * 表示 Dify DSL 工作流配置
 */
class Workflow
{
    public function __construct(
        private Graph $graph,
        /** @var Variable[] */
        private array $environmentVariables = [],
        /** @var Variable[] */
        private array $conversationVariables = [],
        /** @var array<string, mixed> */
        private array $features = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<string, mixed> $graphData */
        $graphData = isset($data['graph']) && is_array($data['graph']) ? $data['graph'] : [];
        $graph = Graph::fromArray($graphData);

        /** @var array<string, mixed> $features */
        $features = isset($data['features']) && is_array($data['features']) ? $data['features'] : [];

        return new self(
            graph: $graph,
            environmentVariables: self::parseVariables($data, 'environment_variables'),
            conversationVariables: self::parseVariables($data, 'conversation_variables'),
            features: $features
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return Variable[]
     */
    private static function parseVariables(array $data, string $key): array
    {
        $variables = [];
        if (isset($data[$key]) && is_array($data[$key])) {
            foreach ($data[$key] as $varData) {
                if (!is_array($varData)) {
                    continue;
                }
                /** @var array<string, mixed> $varData */
                $variables[] = Variable::fromArray($varData);
            }
        }

        return $variables;
    }

    public function getGraph(): Graph
    {
        return $this->graph;
    }

    public function setGraph(Graph $graph): void
    {
        $this->graph = $graph;
    }

    /**
     * @return Variable[]
     */
    public function getEnvironmentVariables(): array
    {
        return $this->environmentVariables;
    }

    public function addEnvironmentVariable(Variable $variable): self
    {
        $this->environmentVariables[] = $variable;

        return $this;
    }

    /**
     * @param Variable[] $variables
     */
    public function setEnvironmentVariables(array $variables): void
    {
        $this->environmentVariables = $variables;
    }

    /**
     * @return Variable[]
     */
    public function getConversationVariables(): array
    {
        return $this->conversationVariables;
    }

    public function addConversationVariable(Variable $variable): self
    {
        $this->conversationVariables[] = $variable;

        return $this;
    }

    /**
     * @param Variable[] $variables
     */
    public function setConversationVariables(array $variables): void
    {
        $this->conversationVariables = $variables;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFeatures(): array
    {
        return $this->features;
    }

    /**
     * @param array<string, mixed> $features
     */
    public function setFeatures(array $features): void
    {
        $this->features = $features;
    }

    public function getFeature(string $name): mixed
    {
        return $this->features[$name] ?? null;
    }

    public function setFeature(string $name, mixed $value): void
    {
        $this->features[$name] = $value;
    }

    public function removeFeature(string $name): self
    {
        unset($this->features[$name]);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'graph' => $this->graph->toArray(),
        ];

        if ([] !== $this->environmentVariables) {
            $data['environment_variables'] = array_map(
                fn (Variable $var) => $var->toArray(),
                $this->environmentVariables
            );
        }

        if ([] !== $this->conversationVariables) {
            $data['conversation_variables'] = array_map(
                fn (Variable $var) => $var->toArray(),
                $this->conversationVariables
            );
        }

        if ([] !== $this->features) {
            $data['features'] = $this->features;
        }

        return $data;
    }
}
