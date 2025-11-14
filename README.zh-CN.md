# Dify DSL PHP 库

[English](README.md) | [中文](README.zh-CN.md)

一个框架无关的 PHP 库，用于解析和生成 Dify DSL（领域特定语言）工作流。

[![PHP 版本](https://img.shields.io/badge/php-%5E8.2-blue)](https://packagist.org/packages/tourze/dify-dsl)
[![许可证](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## 特性

- **工作流构建器** - 流畅的 API，轻松构建复杂的工作流
- **DSL 解析器** - 将现有的 Dify DSL YAML 文件解析为 PHP 对象
- **代码生成** - 从 PHP 对象生成有效的 Dify DSL YAML
- **节点类型** - 支持所有主要的 Dify 节点类型
- **类型安全** - 完整的 PHP 8.2+ 类型声明
- **验证** - 内置的工作流结构验证
- **可扩展** - 易于扩展自定义节点类型

## 安装

```bash
composer require tourze/dify-dsl
```

## 使用方法

### 1. 使用流畅 API 构建工作流

```php
<?php

use Tourze\DifyDsl\Builder\WorkflowBuilder;
use Tourze\DifyDsl\Generator\DifyGenerator;

// 创建一个 AI 助手工作流
$app = WorkflowBuilder::create()
    ->setName("AI 助手")
    ->setDescription("一个有用的 AI 助手")
    ->setMode("workflow")
    ->setIcon("🤖")

    // 添加开始节点
    ->addStartNode(function($node) {
        $node->addVariableFromArray("query", "text-input", true, "用户查询");
        $node->addVariableFromArray("context", "paragraph", false, "上下文信息");
    })

    // 添加 LLM 节点
    ->addLLMNode(null, function($node) {
        $node->setTitle("智能回答")
             ->setModel("gpt-4", "openai", "chat")
             ->setSystemPrompt("你是一个有用的 AI 助手")
             ->setUserPrompt("用户查询：{{#start.query#}}\n\n上下文：{{#start.context#}}");
    })

    // 添加结束节点
    ->addEndNode(function($node) {
        $node->addOutput("result", ["llm", "text"]);
    })

    ->build();

// 生成 YAML
$generator = new DifyGenerator();
$yaml = $generator->generatePretty($app);
echo $yaml;
```

### 2. 解析现有的 YAML 文件

```php
<?php

use Tourze\DifyDsl\Parser\DifyParser;

$parser = new DifyParser();
$app = $parser->parseFile('workflow.yml');

echo "应用名称: " . $app->getName() . "\n";
echo "节点数量: " . count($app->getWorkflow()->getGraph()->getNodes()) . "\n";

// 遍历节点
$nodes = $app->getWorkflow()->getGraph()->getNodes();
foreach ($nodes as $node) {
    echo "节点: " . $node->getId() . " (" . $node->getNodeType() . ")\n";
}
```

## 支持的节点类型

该库支持所有主要的 Dify 工作流节点类型：

- **StartNode** - 工作流的入口变量节点
- **EndNode** - Workflow 模式的结束节点
- **AnswerNode** - Chatflow 模式的结束节点
- **LLMNode** - 大语言模型节点
- **ToolNode** - 工具/函数调用节点
- **CodeNode** - 自定义代码执行节点

## API 参考

### WorkflowBuilder

| 方法 | 描述 |
|------|------|
| `setName(string $name)` | 设置工作流名称 |
| `setDescription(string $desc)` | 设置工作流描述 |
| `setMode(string $mode)` | 设置工作流模式 |
| `addStartNode(?callable $config)` | 添加开始节点 |
| `addLLMNode(?string $id, ?callable $config)` | 添加 LLM 节点 |
| `addEndNode(?callable $config)` | 添加结束节点 |
| `build()` | 构建工作流应用 |

### DifyParser

| 方法 | 描述 |
|------|------|
| `parse(string $yaml)` | 解析 YAML 字符串 |
| `parseFile(string $path)` | 解析 YAML 文件 |
| `parseFromArray(array $data)` | 从数组解析 |

### DifyGenerator

| 方法 | 描述 |
|------|------|
| `generate(App $app)` | 生成 YAML 字符串 |
| `generateToFile(App $app, string $path)` | 生成到文件 |
| `generatePretty(App $app)` | 生成格式化的 YAML |

## 示例

查看 [`examples/`](examples/) 目录获取完整的使用示例：

- [`simple_workflow.php`](examples/simple_workflow.php) - 基本的工作流创建和解析

## 贡献

欢迎贡献！请随时提交 Pull Request。

## 许可证

本项目采用 MIT 许可证 - 详情请参阅 [LICENSE](LICENSE) 文件。