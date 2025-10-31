<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Core;

/**
 * 表示 Dify DSL 中的边（连接）
 */
class Edge
{
    public function __construct(
        private string $id,
        private string $source,
        private string $target,
        private string $type = 'custom',
        private ?string $sourceHandle = null,
        private ?string $targetHandle = null,
        private bool $selected = false,
        /** @var array<string, mixed> */
        private array $data = [],
        private int $zIndex = 0,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $edgeData = $data['data'] ?? [];
        if (!is_array($edgeData)) {
            $edgeData = [];
        }
        /** @var array<string, mixed> $edgeData */

        return new self(
            id: is_string($data['id'] ?? null) ? $data['id'] : '',
            source: is_string($data['source'] ?? null) ? $data['source'] : '',
            target: is_string($data['target'] ?? null) ? $data['target'] : '',
            type: is_string($data['type'] ?? null) ? $data['type'] : 'custom',
            sourceHandle: is_string($data['sourceHandle'] ?? null) ? $data['sourceHandle'] : null,
            targetHandle: is_string($data['targetHandle'] ?? null) ? $data['targetHandle'] : null,
            selected: is_bool($data['selected'] ?? null) ? $data['selected'] : false,
            data: $edgeData,
            zIndex: is_int($data['zIndex'] ?? null) ? $data['zIndex'] : 0
        );
    }

    public static function create(string $source, string $target, ?string $id = null): self
    {
        return new self(
            id: $id ?? sprintf('%s-%s', $source, $target),
            source: $source,
            target: $target
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSourceHandle(): ?string
    {
        return $this->sourceHandle;
    }

    public function getTargetHandle(): ?string
    {
        return $this->targetHandle;
    }

    public function isSelected(): bool
    {
        return $this->selected;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getZIndex(): int
    {
        return $this->zIndex;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'type' => $this->type,
            'source' => $this->source,
            'target' => $this->target,
            'selected' => $this->selected,
        ];

        if (null !== $this->sourceHandle) {
            $data['sourceHandle'] = $this->sourceHandle;
        }

        if (null !== $this->targetHandle) {
            $data['targetHandle'] = $this->targetHandle;
        }

        if ([] !== $this->data) {
            $data['data'] = $this->data;
        }

        if (0 !== $this->zIndex) {
            $data['zIndex'] = $this->zIndex;
        }

        return $data;
    }
}
