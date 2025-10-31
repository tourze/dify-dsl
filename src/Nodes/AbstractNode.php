<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Nodes;

/**
 * 所有节点的抽象基类
 */
abstract class AbstractNode
{
    /** @var array{x: int, y: int} */
    protected array $position = ['x' => 0, 'y' => 0];

    /** @var array{x: int, y: int}|null */
    protected ?array $positionAbsolute = null;

    protected ?int $width = null;

    protected ?int $height = null;

    protected string $sourcePosition = 'right';

    protected string $targetPosition = 'left';

    protected bool $selected = false;

    protected ?string $parentId = null;

    protected ?string $extent = null;

    protected ?int $zIndex = null;

    protected ?bool $selectable = null;

    protected ?bool $draggable = null;

    public function __construct(
        protected string $id,
        protected string $title = '',
        protected string $description = '',
        protected string $uiType = 'custom',
    ) {
    }

    /** @param array<string, mixed> $data */
    abstract public static function fromArray(array $data): static;

    /** @param array<string, mixed> $data */
    protected static function setBaseProperties(self $node, array $data): void
    {
        self::setPositionProperties($node, $data);
        self::setSizeProperties($node, $data);
        self::setLayoutProperties($node, $data);
        self::setStateProperties($node, $data);
    }

    /** @param array<string, mixed> $data */
    private static function setPositionProperties(self $node, array $data): void
    {
        if (isset($data['position']) && is_array($data['position'])) {
            /** @var array{x: int, y: int} $position */
            $position = $data['position'];
            $node->position = $position;
        }

        if (isset($data['positionAbsolute']) && is_array($data['positionAbsolute'])) {
            /** @var array{x: int, y: int} $positionAbsolute */
            $positionAbsolute = $data['positionAbsolute'];
            $node->positionAbsolute = $positionAbsolute;
        }

        if (isset($data['sourcePosition']) && is_string($data['sourcePosition'])) {
            $node->sourcePosition = $data['sourcePosition'];
        }

        if (isset($data['targetPosition']) && is_string($data['targetPosition'])) {
            $node->targetPosition = $data['targetPosition'];
        }
    }

    /** @param array<string, mixed> $data */
    private static function setSizeProperties(self $node, array $data): void
    {
        if (isset($data['width']) && is_int($data['width'])) {
            $node->width = $data['width'];
        }

        if (isset($data['height']) && is_int($data['height'])) {
            $node->height = $data['height'];
        }
    }

    /** @param array<string, mixed> $data */
    private static function setLayoutProperties(self $node, array $data): void
    {
        if (isset($data['parentId']) && is_string($data['parentId'])) {
            $node->parentId = $data['parentId'];
        }

        if (isset($data['extent']) && is_string($data['extent'])) {
            $node->extent = $data['extent'];
        }

        if (isset($data['zIndex']) && is_int($data['zIndex'])) {
            $node->zIndex = $data['zIndex'];
        }
    }

    /** @param array<string, mixed> $data */
    private static function setStateProperties(self $node, array $data): void
    {
        if (isset($data['selected']) && is_bool($data['selected'])) {
            $node->selected = $data['selected'];
        }

        if (isset($data['selectable']) && is_bool($data['selectable'])) {
            $node->selectable = $data['selectable'];
        }

        if (isset($data['draggable']) && is_bool($data['draggable'])) {
            $node->draggable = $data['draggable'];
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /** @return array{x: int, y: int} */
    public function getPosition(): array
    {
        return $this->position;
    }

    public function setPosition(int $x, int $y): void
    {
        $this->position = ['x' => $x, 'y' => $y];
    }

    public function setSelected(bool $selected): void
    {
        $this->selected = $selected;
    }

    public function setParentId(?string $parentId): void
    {
        $this->parentId = $parentId;
    }

    public function setExtent(?string $extent): void
    {
        $this->extent = $extent;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'type' => $this->uiType,
            'position' => $this->position,
            'data' => $this->getNodeData(),
        ];

        $data = $this->addOptionalProperties($data);

        return $this->addNonDefaultProperties($data);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function addOptionalProperties(array $data): array
    {
        if (null !== $this->positionAbsolute) {
            $data['positionAbsolute'] = $this->positionAbsolute;
        }

        if (null !== $this->width) {
            $data['width'] = $this->width;
        }

        if (null !== $this->height) {
            $data['height'] = $this->height;
        }

        if (null !== $this->parentId) {
            $data['parentId'] = $this->parentId;
        }

        if (null !== $this->extent) {
            $data['extent'] = $this->extent;
        }

        if (null !== $this->zIndex) {
            $data['zIndex'] = $this->zIndex;
        }

        if (null !== $this->selectable) {
            $data['selectable'] = $this->selectable;
        }

        if (null !== $this->draggable) {
            $data['draggable'] = $this->draggable;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function addNonDefaultProperties(array $data): array
    {
        if ('right' !== $this->sourcePosition) {
            $data['sourcePosition'] = $this->sourcePosition;
        }

        if ('left' !== $this->targetPosition) {
            $data['targetPosition'] = $this->targetPosition;
        }

        if ($this->selected) {
            $data['selected'] = $this->selected;
        }

        return $data;
    }

    /** @return array<string, mixed> */
    protected function getNodeData(): array
    {
        return [
            'type' => $this->getNodeType(),
            'title' => $this->title,
            'desc' => $this->description,
            'selected' => $this->selected,
        ];
    }

    abstract public function getNodeType(): string;
}
