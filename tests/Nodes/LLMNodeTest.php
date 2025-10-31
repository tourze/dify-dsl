<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Nodes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyDsl\Nodes\LLMNode;

/**
 * @internal
 */
#[CoversClass(LLMNode::class)]
class LLMNodeTest extends TestCase
{
    public function testCreateLLMNode(): void
    {
        $node = new LLMNode('llm_id', 'LLM Title', 'LLM Description');

        $this->assertEquals('llm_id', $node->getId());
        $this->assertEquals('LLM Title', $node->getTitle());
        $this->assertEquals('LLM Description', $node->getDescription());
        $this->assertEquals('llm', $node->getNodeType());
        $this->assertEmpty($node->getModel());
        $this->assertEmpty($node->getPromptTemplate());
        $this->assertNull($node->getContext());
        $this->assertNull($node->getMemory());
        $this->assertNull($node->getVision());
        $this->assertEmpty($node->getVariables());
        $this->assertFalse($node->isStructuredOutputEnabled());
        $this->assertNull($node->getOutputSchema());
    }

    public function testCreateLLMNodeWithDefaults(): void
    {
        $node = new LLMNode('llm');

        $this->assertEquals('llm', $node->getId());
        $this->assertEquals('LLM', $node->getTitle());
        $this->assertEquals('', $node->getDescription());
    }

    public function testCreateFactoryMethod(): void
    {
        $node = LLMNode::create();

        $this->assertEquals('llm', $node->getId());
        $this->assertEquals('LLM', $node->getTitle());
        $this->assertEquals('llm', $node->getNodeType());
    }

    public function testCreateFactoryMethodWithCustomId(): void
    {
        $node = LLMNode::create('custom_llm');

        $this->assertEquals('custom_llm', $node->getId());
        $this->assertEquals('LLM', $node->getTitle());
    }

    public function testSetModel(): void
    {
        $node = new LLMNode('llm');

        $node->setModel('gpt-4', 'openai', 'chat', ['temperature' => 0.7]);

        $model = $node->getModel();
        $this->assertEquals('chat', $model['mode']);
        $this->assertEquals('gpt-4', $model['name']);
        $this->assertEquals('openai', $model['provider']);
        $this->assertEquals(['temperature' => 0.7], $model['completion_params']);
    }

    public function testSetModelWithoutCompletionParams(): void
    {
        $node = new LLMNode('llm');

        $node->setModel('claude-3', 'anthropic');

        $model = $node->getModel();
        $this->assertEquals('chat', $model['mode']);
        $this->assertEquals('claude-3', $model['name']);
        $this->assertEquals('anthropic', $model['provider']);
        $this->assertArrayNotHasKey('completion_params', $model);
    }

    public function testSetModelFromArray(): void
    {
        $node = new LLMNode('llm');
        $modelArray = [
            'mode' => 'completion',
            'name' => 'text-davinci-003',
            'provider' => 'openai',
            'completion_params' => ['max_tokens' => 1000],
        ];

        $node->setModelFromArray($modelArray);

        $this->assertEquals($modelArray, $node->getModel());
    }

    public function testAddPromptMessage(): void
    {
        $node = new LLMNode('llm');

        $result = $node->addPromptMessage('system', 'You are a helpful assistant', 'basic', 'msg1');

        $this->assertSame($node, $result); // 测试流式接口

        $template = $node->getPromptTemplate();
        $this->assertCount(1, $template);

        $message = $template[0];
        $this->assertEquals('system', $message['role']);
        $this->assertEquals('You are a helpful assistant', $message['text']);
        $this->assertEquals('basic', $message['edition_type']);
        $this->assertEquals('msg1', $message['id']);
    }

    public function testAddPromptMessageMinimal(): void
    {
        $node = new LLMNode('llm');

        $node->addPromptMessage('user', 'Hello world');

        $template = $node->getPromptTemplate();
        $message = $template[0];
        $this->assertEquals('user', $message['role']);
        $this->assertEquals('Hello world', $message['text']);
        $this->assertArrayNotHasKey('edition_type', $message);
        $this->assertArrayNotHasKey('id', $message);
    }

    public function testSetSystemPrompt(): void
    {
        $node = new LLMNode('llm');

        // 首先添加一些消息
        $node->addPromptMessage('user', 'User message')
            ->addPromptMessage('system', 'Old system prompt')
        ;

        $node->setSystemPrompt('New system prompt');

        $template = $node->getPromptTemplate();
        $this->assertCount(2, $template); // 用户消息保留，系统消息被替换

        // 系统消息应该在第一位
        $this->assertEquals('system', $template[0]['role']);
        $this->assertEquals('New system prompt', $template[0]['text']);

        // 用户消息应该保留
        $this->assertEquals('user', $template[1]['role']);
        $this->assertEquals('User message', $template[1]['text']);
    }

    public function testSetUserPrompt(): void
    {
        $node = new LLMNode('llm');

        // 首先添加一些消息
        $node->addPromptMessage('system', 'System message')
            ->addPromptMessage('user', 'Old user prompt')
        ;

        $node->setUserPrompt('New user prompt');

        $template = $node->getPromptTemplate();
        $this->assertCount(2, $template); // 系统消息保留，用户消息被替换

        // 系统消息应该保留
        $this->assertEquals('system', $template[0]['role']);
        $this->assertEquals('System message', $template[0]['text']);

        // 新用户消息应该在最后
        $this->assertEquals('user', $template[1]['role']);
        $this->assertEquals('New user prompt', $template[1]['text']);
    }

    public function testSetPromptTemplate(): void
    {
        $node = new LLMNode('llm');
        $template = [
            ['role' => 'system', 'text' => 'System prompt'],
            ['role' => 'user', 'text' => 'User prompt'],
        ];

        $node->setPromptTemplate($template);

        $this->assertEquals($template, $node->getPromptTemplate());
    }

    public function testEnableContext(): void
    {
        $node = new LLMNode('llm');

        // @phpstan-ignore-next-line
        $result = $node->enableContext(['context_node', 'output']);

        $this->assertSame($node, $result); // 测试流式接口

        $context = $node->getContext();
        $this->assertIsArray($context);
        $this->assertTrue($context['enabled'] ?? false);
        $this->assertEquals(['context_node', 'output'], $context['variable_selector'] ?? []);
    }

    public function testDisableContext(): void
    {
        $node = new LLMNode('llm');

        // 先启用上下文
        // @phpstan-ignore-next-line
        $node->enableContext(['some_node', 'output']);

        $result = $node->disableContext();

        $this->assertSame($node, $result); // 测试流式接口

        $context = $node->getContext();
        $this->assertIsArray($context);
        $this->assertFalse($context['enabled'] ?? true);
    }

    public function testSetMemory(): void
    {
        $node = new LLMNode('llm');
        $memory = [
            'enabled' => true,
            'type' => 'sliding_window',
            'window_size' => 10,
        ];

        $node->setMemory($memory);

        $this->assertEquals($memory, $node->getMemory());
    }

    public function testEnableVision(): void
    {
        $node = new LLMNode('llm');

        // @phpstan-ignore-next-line
        $result = $node->enableVision(['image_node', 'image'], 'high');

        $this->assertSame($node, $result); // 测试流式接口

        $vision = $node->getVision();
        $this->assertIsArray($vision);
        $this->assertTrue($vision['enabled'] ?? false);
        $configs = $vision['configs'] ?? [];
        $this->assertIsArray($configs);
        $this->assertEquals('high', $configs['detail'] ?? '');
        $this->assertEquals(['image_node', 'image'], $configs['variable_selector'] ?? []);
    }

    public function testEnableVisionWithDefaults(): void
    {
        $node = new LLMNode('llm');

        // @phpstan-ignore-next-line
        $node->enableVision(['image_source', 'data']);

        $vision = $node->getVision();
        $this->assertIsArray($vision);
        $this->assertTrue($vision['enabled'] ?? false);
        $configs = $vision['configs'] ?? [];
        $this->assertIsArray($configs);
        $this->assertEquals('auto', $configs['detail'] ?? ''); // 默认值
    }

    public function testDisableVision(): void
    {
        $node = new LLMNode('llm');

        // 先启用视觉
        // @phpstan-ignore-next-line
        $node->enableVision(['image_node', 'image']);

        $result = $node->disableVision();

        $this->assertSame($node, $result); // 测试流式接口

        $vision = $node->getVision();
        $this->assertIsArray($vision);
        $this->assertFalse($vision['enabled'] ?? true);
    }

    public function testSetVariables(): void
    {
        $node = new LLMNode('llm');
        $variables = [
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'custom_param' => 'value',
        ];

        $node->setVariables($variables);

        $this->assertEquals($variables, $node->getVariables());
    }

    public function testEnableStructuredOutput(): void
    {
        $node = new LLMNode('llm');
        $schema = [
            'type' => 'object',
            'properties' => [
                'answer' => ['type' => 'string'],
                'confidence' => ['type' => 'number'],
            ],
        ];

        $result = $node->enableStructuredOutput($schema);

        $this->assertSame($node, $result); // 测试流式接口
        $this->assertTrue($node->isStructuredOutputEnabled());
        $this->assertEquals($schema, $node->getOutputSchema());
    }

    public function testDisableStructuredOutput(): void
    {
        $node = new LLMNode('llm');

        // 先启用结构化输出
        $node->enableStructuredOutput(['type' => 'object']);

        $result = $node->disableStructuredOutput();

        $this->assertSame($node, $result); // 测试流式接口
        $this->assertFalse($node->isStructuredOutputEnabled());
        $this->assertNull($node->getOutputSchema());
    }

    public function testFromArray(): void
    {
        $data = [
            'id' => 'llm_node',
            'type' => 'custom',
            'position' => ['x' => 200, 'y' => 300],
            'data' => [
                'title' => 'GPT-4 Node',
                'desc' => 'OpenAI GPT-4 processing',
                'model' => [
                    'mode' => 'chat',
                    'name' => 'gpt-4',
                    'provider' => 'openai',
                    'completion_params' => ['temperature' => 0.8],
                ],
                'prompt_template' => [
                    ['role' => 'system', 'text' => 'You are helpful'],
                    ['role' => 'user', 'text' => 'Process this: {{input}}'],
                ],
                'context' => [
                    'enabled' => true,
                    'variable_selector' => ['context_node', 'context'],
                ],
                'memory' => [
                    'enabled' => true,
                    'type' => 'buffer',
                ],
                'vision' => [
                    'enabled' => true,
                    'configs' => [
                        'detail' => 'high',
                        'variable_selector' => ['image_input', 'image'],
                    ],
                ],
                'variables' => [
                    'custom_var' => 'value',
                ],
                'structured_output_enabled' => true,
                'output_schema' => [
                    'type' => 'object',
                    'properties' => ['result' => ['type' => 'string']],
                ],
            ],
        ];

        $node = LLMNode::fromArray($data);

        $this->assertEquals('llm_node', $node->getId());
        $this->assertEquals('GPT-4 Node', $node->getTitle());
        $this->assertEquals('OpenAI GPT-4 processing', $node->getDescription());
        $this->assertEquals(['x' => 200, 'y' => 300], $node->getPosition());

        // 验证模型配置
        $model = $node->getModel();
        $this->assertEquals('gpt-4', $model['name']);
        $this->assertEquals('openai', $model['provider']);
        $completionParams = $model['completion_params'] ?? [];
        $this->assertIsArray($completionParams);
        $this->assertEquals(0.8, $completionParams['temperature'] ?? 0);

        // 验证提示模板
        $template = $node->getPromptTemplate();
        $this->assertCount(2, $template);
        $this->assertEquals('system', $template[0]['role']);
        $this->assertEquals('user', $template[1]['role']);

        // 验证上下文
        $context = $node->getContext();
        $this->assertIsArray($context);
        $this->assertTrue($context['enabled'] ?? false);
        $this->assertEquals(['context_node', 'context'], $context['variable_selector'] ?? []);

        // 验证内存
        $memory = $node->getMemory();
        $this->assertIsArray($memory);
        $this->assertTrue($memory['enabled'] ?? false);
        $this->assertEquals('buffer', $memory['type'] ?? '');

        // 验证视觉
        $vision = $node->getVision();
        $this->assertIsArray($vision);
        $this->assertTrue($vision['enabled'] ?? false);
        $visionConfigs = $vision['configs'] ?? [];
        $this->assertIsArray($visionConfigs);
        $this->assertEquals('high', $visionConfigs['detail'] ?? '');

        // 验证变量
        $variables = $node->getVariables();
        $this->assertEquals('value', $variables['custom_var']);

        // 验证结构化输出
        $this->assertTrue($node->isStructuredOutputEnabled());
        $schema = $node->getOutputSchema();
        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type'] ?? '');
    }

    public function testFromArrayWithDefaults(): void
    {
        $data = [
            'id' => 'minimal_llm',
        ];

        $node = LLMNode::fromArray($data);

        $this->assertEquals('minimal_llm', $node->getId());
        $this->assertEquals('LLM', $node->getTitle());
        $this->assertEquals('', $node->getDescription());
        $this->assertEmpty($node->getModel());
        $this->assertEmpty($node->getPromptTemplate());
        $this->assertNull($node->getContext());
        $this->assertNull($node->getMemory());
        $this->assertNull($node->getVision());
        $this->assertEmpty($node->getVariables());
        $this->assertFalse($node->isStructuredOutputEnabled());
    }

    public function testToArray(): void
    {
        $node = new LLMNode('complex_llm', 'Complex LLM', 'A complex LLM node');
        $node->setPosition(100, 200);
        $node->setModel('gpt-4', 'openai', 'chat', ['temperature' => 0.7]);
        $node->setSystemPrompt('You are helpful');
        $node->setUserPrompt('Process: {{input}}');
        // @phpstan-ignore-next-line
        $node->enableContext(['ctx', 'data']);
        // @phpstan-ignore-next-line
        $node->enableVision(['img', 'data'], 'high');
        $node->setVariables(['custom' => 'value']);
        $node->enableStructuredOutput(['type' => 'object']);

        $array = $node->toArray();

        $this->assertEquals('complex_llm', $array['id']);
        $this->assertEquals(['x' => 100, 'y' => 200], $array['position']);

        $nodeData = $array['data'];
        $this->assertIsArray($nodeData);
        $this->assertEquals('llm', $nodeData['type'] ?? '');
        $this->assertEquals('Complex LLM', $nodeData['title'] ?? '');
        $this->assertEquals('A complex LLM node', $nodeData['desc'] ?? '');

        // 验证模型数据
        $this->assertIsArray($nodeData);
        $this->assertArrayHasKey('model', $nodeData);
        $model = $nodeData['model'] ?? [];
        $this->assertIsArray($model);
        $this->assertEquals('gpt-4', $model['name'] ?? '');

        // 验证提示模板
        $this->assertArrayHasKey('prompt_template', $nodeData);
        $this->assertIsArray($nodeData['prompt_template']);
        $this->assertCount(2, $nodeData['prompt_template']);

        // 验证其他配置
        $this->assertArrayHasKey('context', $nodeData);
        $this->assertArrayHasKey('vision', $nodeData);
        $this->assertArrayHasKey('variables', $nodeData);
        $this->assertTrue($nodeData['structured_output_enabled']);
        $this->assertArrayHasKey('output_schema', $nodeData);
    }

    public function testToArrayWithMinimalData(): void
    {
        $node = new LLMNode('simple_llm');

        $array = $node->toArray();

        $nodeData = $array['data'];
        $this->assertIsArray($nodeData);

        // 空数组和 null 值不应该出现在输出中
        $this->assertArrayNotHasKey('model', $nodeData);
        $this->assertArrayNotHasKey('prompt_template', $nodeData);
        $this->assertArrayNotHasKey('context', $nodeData);
        $this->assertArrayNotHasKey('memory', $nodeData);
        $this->assertArrayNotHasKey('vision', $nodeData);
        $this->assertArrayNotHasKey('variables', $nodeData);
        $this->assertArrayNotHasKey('structured_output_enabled', $nodeData);
        $this->assertArrayNotHasKey('output_schema', $nodeData);
    }

    public function testComplexPromptConfiguration(): void
    {
        $node = new LLMNode('prompt_test');

        $node->addPromptMessage('system', 'System prompt')
            ->addPromptMessage('user', 'User message 1')
            ->addPromptMessage('assistant', 'Assistant response')
            ->addPromptMessage('user', 'User message 2')
        ;

        $template = $node->getPromptTemplate();
        $this->assertCount(4, $template);

        $roles = array_column($template, 'role');
        $this->assertEquals(['system', 'user', 'assistant', 'user'], $roles);
    }

    public function testFluentConfiguration(): void
    {
        $node = LLMNode::create('fluent_llm');

        $node->setModel('claude-3', 'anthropic');
        $node->setSystemPrompt('You are helpful');
        // @phpstan-ignore-next-line
        $node->enableContext(['ctx', 'output']);
        // @phpstan-ignore-next-line
        $node->enableVision(['img', 'data']);
        $result = $node->enableStructuredOutput(['type' => 'object']);

        $this->assertSame($node, $result);
        $this->assertEquals('claude-3', $node->getModel()['name']);
        $this->assertNotEmpty($node->getPromptTemplate());
        $this->assertNotNull($node->getContext());
        $this->assertNotNull($node->getVision());
        $this->assertTrue($node->isStructuredOutputEnabled());
    }

    public function testRoundTripSerialization(): void
    {
        $originalData = [
            'id' => 'roundtrip_llm',
            'position' => ['x' => 150, 'y' => 250],
            'data' => [
                'title' => 'Round Trip LLM',
                'model' => ['name' => 'gpt-3.5-turbo', 'provider' => 'openai'],
                'prompt_template' => [['role' => 'user', 'text' => 'Hello']],
                'structured_output_enabled' => true,
                'output_schema' => ['type' => 'string'],
            ],
        ];

        $node = LLMNode::fromArray($originalData);
        $serialized = $node->toArray();

        // 验证核心属性保持一致
        $this->assertEquals($originalData['id'], $serialized['id']);
        $this->assertEquals($originalData['position'], $serialized['position']);

        $originalDataData = $originalData['data'];
        $serializedData = $serialized['data'];
        $this->assertIsArray($serializedData);

        $this->assertEquals($originalDataData['title'], $serializedData['title'] ?? '');
        $this->assertEquals($originalDataData['model'], $serializedData['model'] ?? []);
        $this->assertEquals($originalDataData['prompt_template'], $serializedData['prompt_template'] ?? []);
        $this->assertEquals($originalDataData['structured_output_enabled'], $serializedData['structured_output_enabled'] ?? false);
        $this->assertEquals($originalDataData['output_schema'], $serializedData['output_schema'] ?? []);
    }
}
