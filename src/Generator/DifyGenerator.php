<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Generator;

use Symfony\Component\Yaml\Yaml;
use Tourze\DifyDsl\Core\App;

/**
 * Dify DSL 生成器 - 将对象模型转换为 YAML
 */
class DifyGenerator
{
    private int $flags;

    public function __construct(
        private int $indentSize = 2,
        int $flags = 0,
    ) {
        if (0 === $flags) {
            $this->flags = Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE;
        } else {
            $this->flags = $flags;
        }
    }

    /**
     * 生成并保存到文件
     */
    public function generateToFile(App $app, string $filePath): void
    {
        $yaml = $this->generate($app);

        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0o755, true);
        }

        file_put_contents($filePath, $yaml);
    }

    /**
     * 生成 YAML 字符串
     */
    public function generate(App $app): string
    {
        $data = $app->toArray();

        return Yaml::dump($data, 10, $this->indentSize, $this->flags);
    }

    /**
     * 美化输出格式
     */
    public function generatePretty(App $app): string
    {
        $data = $app->toArray();

        // 使用更好的格式化选项
        return Yaml::dump(
            $data,
            10,
            $this->indentSize,
            Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK |
            Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE |
            Yaml::DUMP_NULL_AS_TILDE
        );
    }

    /**
     * 设置缩进大小
     */
    public function setIndentSize(int $size): void
    {
        $this->indentSize = $size;
    }

    /**
     * 设置 YAML 输出标志
     */
    public function setFlags(int $flags): void
    {
        $this->flags = $flags;
    }
}
