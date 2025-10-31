<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Tourze\DifyDsl\Builder\WorkflowBuilder;
use Tourze\DifyDsl\Generator\DifyGenerator;
use Tourze\DifyDsl\Parser\DifyParser;

// 示例1：使用流式 API 构建工作流
$app = WorkflowBuilder::create()
    ->setName('AI Assistant')
    ->setDescription('A simple AI assistant workflow')
    ->setMode('workflow')
    ->setIcon('🤖')

    // 添加开始节点
    ->addStartNode(function ($node) {
        $node->addVariableFromArray('query', 'text-input', true, '用户查询');
        $node->addVariableFromArray('context', 'paragraph', false, '上下文信息');
    })

    // 添加 LLM 节点
    ->addLLMNode(null, function ($node) {
        $node->setTitle('智能回答')
            ->setModel('gpt-4', 'openai', 'chat')
            ->setSystemPrompt('你是一个专业的AI助手，请根据用户的查询提供准确的回答。')
            ->setUserPrompt("用户查询：{{#start.query#}}\n\n上下文：{{#start.context#}}")
        ;
    })

    // 添加结束节点
    ->addEndNode(function ($node) {
        $node->addOutput('result', ['llm_' . time() . '_' . mt_rand(1000, 9999), 'text']);
    })

    ->build()
;

// 生成 YAML
$generator = new DifyGenerator();
$yaml = $generator->generatePretty($app);

echo "生成的 Dify DSL:\n";
echo "================\n";
echo $yaml;

// 保存到文件
$generator->generateToFile($app, __DIR__ . '/workflows/simple_workflow.yml');

echo "\n\n工作流已保存到: " . __DIR__ . '/workflows/simple_workflow.yml';

// 示例2：解析现有的 YAML 文件
try {
    $parser = new DifyParser();
    $parsedApp = $parser->parseFile(__DIR__ . '/workflows/simple_workflow.yml');

    echo "\n\n解析成功！";
    echo "\n应用名称: " . $parsedApp->getName();
    echo "\n应用模式: " . $parsedApp->getMode();
    echo "\n节点数量: " . count($parsedApp->getWorkflow()->getGraph()->getNodes());
} catch (Exception $e) {
    echo "\n\n解析失败: " . $e->getMessage();
}
