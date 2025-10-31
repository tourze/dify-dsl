<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Core;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyDsl\Core\Graph;
use Tourze\DifyDsl\Core\Variable;
use Tourze\DifyDsl\Core\Workflow;

/**
 * @internal
 */
#[CoversClass(Workflow::class)]
class WorkflowTest extends TestCase
{
    public function testCreateWorkflow(): void
    {
        $graph = new Graph();
        $workflow = new Workflow($graph);

        $this->assertSame($graph, $workflow->getGraph());
        $this->assertEmpty($workflow->getEnvironmentVariables());
        $this->assertEmpty($workflow->getConversationVariables());
        $this->assertEmpty($workflow->getFeatures());
    }

    public function testCreateWorkflowWithVariablesAndFeatures(): void
    {
        $graph = new Graph();
        $envVar = new Variable('env_var', 'Environment Variable', 'text-input');
        $convVar = new Variable('conv_var', 'Conversation Variable', 'select');
        $features = ['speech_to_text' => true, 'file_upload' => ['enabled' => true]];

        $workflow = new Workflow(
            graph: $graph,
            environmentVariables: [$envVar],
            conversationVariables: [$convVar],
            features: $features
        );

        $this->assertSame($graph, $workflow->getGraph());
        $this->assertCount(1, $workflow->getEnvironmentVariables());
        $this->assertContains($envVar, $workflow->getEnvironmentVariables());
        $this->assertCount(1, $workflow->getConversationVariables());
        $this->assertContains($convVar, $workflow->getConversationVariables());
        $this->assertEquals($features, $workflow->getFeatures());
    }

    public function testSetGraph(): void
    {
        $originalGraph = new Graph();
        $newGraph = new Graph();
        $workflow = new Workflow($originalGraph);

        $workflow->setGraph($newGraph);

        $this->assertSame($newGraph, $workflow->getGraph());
        $this->assertNotSame($originalGraph, $workflow->getGraph());
    }

    public function testAddEnvironmentVariable(): void
    {
        $workflow = new Workflow(new Graph());
        $variable = new Variable('api_key', 'API Key', 'text-input', true);

        $result = $workflow->addEnvironmentVariable($variable);

        $this->assertSame($workflow, $result); // 测试流式接口
        $this->assertCount(1, $workflow->getEnvironmentVariables());
        $this->assertContains($variable, $workflow->getEnvironmentVariables());
    }

    public function testSetEnvironmentVariables(): void
    {
        $workflow = new Workflow(new Graph());
        $var1 = new Variable('var1', 'Variable 1', 'text-input');
        $var2 = new Variable('var2', 'Variable 2', 'number');
        $variables = [$var1, $var2];

        $workflow->setEnvironmentVariables($variables);

        $this->assertEquals($variables, $workflow->getEnvironmentVariables());
    }

    public function testAddConversationVariable(): void
    {
        $workflow = new Workflow(new Graph());
        $variable = new Variable('user_name', 'User Name', 'text-input');

        $result = $workflow->addConversationVariable($variable);

        $this->assertSame($workflow, $result); // 测试流式接口
        $this->assertCount(1, $workflow->getConversationVariables());
        $this->assertContains($variable, $workflow->getConversationVariables());
    }

    public function testSetConversationVariables(): void
    {
        $workflow = new Workflow(new Graph());
        $var1 = new Variable('session_id', 'Session ID', 'text-input');
        $var2 = new Variable('user_role', 'User Role', 'select');
        $variables = [$var1, $var2];

        $workflow->setConversationVariables($variables);

        $this->assertEquals($variables, $workflow->getConversationVariables());
    }

    public function testSetFeatures(): void
    {
        $workflow = new Workflow(new Graph());
        $features = [
            'speech_to_text' => true,
            'text_to_speech' => false,
            'file_upload' => ['enabled' => true, 'max_size' => '10MB'],
        ];

        $workflow->setFeatures($features);

        $this->assertEquals($features, $workflow->getFeatures());
    }

    public function testGetFeature(): void
    {
        $features = [
            'speech_to_text' => true,
            'file_upload' => ['enabled' => true],
        ];
        $workflow = new Workflow(new Graph(), [], [], $features);

        $this->assertTrue($workflow->getFeature('speech_to_text'));
        $this->assertEquals(['enabled' => true], $workflow->getFeature('file_upload'));
        $this->assertNull($workflow->getFeature('nonexistent_feature'));
    }

    public function testSetFeature(): void
    {
        $workflow = new Workflow(new Graph());

        $workflow->setFeature('new_feature', 'feature_value');

        $this->assertEquals('feature_value', $workflow->getFeature('new_feature'));
        $this->assertEquals(['new_feature' => 'feature_value'], $workflow->getFeatures());
    }

    public function testRemoveFeature(): void
    {
        $features = ['feature1' => 'value1', 'feature2' => 'value2'];
        $workflow = new Workflow(new Graph(), [], [], $features);

        $result = $workflow->removeFeature('feature1');

        $this->assertSame($workflow, $result); // 测试流式接口
        $this->assertNull($workflow->getFeature('feature1'));
        $this->assertEquals('value2', $workflow->getFeature('feature2'));
        $this->assertEquals(['feature2' => 'value2'], $workflow->getFeatures());
    }

    public function testFromArray(): void
    {
        $data = [
            'graph' => [
                'nodes' => [],
                'edges' => [],
            ],
            'environment_variables' => [
                [
                    'variable' => 'api_key',
                    'label' => 'API Key',
                    'type' => 'text-input',
                    'required' => true,
                ],
            ],
            'conversation_variables' => [
                [
                    'variable' => 'user_id',
                    'label' => 'User ID',
                    'type' => 'text-input',
                ],
            ],
            'features' => [
                'speech_to_text' => true,
                'file_upload' => ['enabled' => false],
            ],
        ];

        $workflow = Workflow::fromArray($data);

        $this->assertInstanceOf(Graph::class, $workflow->getGraph());
        $this->assertCount(1, $workflow->getEnvironmentVariables());
        $this->assertCount(1, $workflow->getConversationVariables());

        $envVar = $workflow->getEnvironmentVariables()[0];
        $this->assertEquals('api_key', $envVar->getVariable());
        $this->assertEquals('API Key', $envVar->getLabel());
        $this->assertTrue($envVar->isRequired());

        $convVar = $workflow->getConversationVariables()[0];
        $this->assertEquals('user_id', $convVar->getVariable());
        $this->assertEquals('User ID', $convVar->getLabel());

        $features = $workflow->getFeatures();
        $this->assertTrue($features['speech_to_text']);
        $this->assertEquals(['enabled' => false], $features['file_upload']);
    }

    public function testFromArrayWithMinimalData(): void
    {
        $data = [];

        $workflow = Workflow::fromArray($data);

        $this->assertInstanceOf(Graph::class, $workflow->getGraph());
        $this->assertEmpty($workflow->getEnvironmentVariables());
        $this->assertEmpty($workflow->getConversationVariables());
        $this->assertEmpty($workflow->getFeatures());
    }

    public function testToArray(): void
    {
        $graph = new Graph();
        $envVar = new Variable('env_test', 'Environment Test', 'text-input');
        $convVar = new Variable('conv_test', 'Conversation Test', 'select');
        $features = ['test_feature' => true];

        $workflow = new Workflow($graph, [$envVar], [$convVar], $features);

        $array = $workflow->toArray();

        $this->assertArrayHasKey('graph', $array);
        $this->assertArrayHasKey('environment_variables', $array);
        $this->assertArrayHasKey('conversation_variables', $array);
        $this->assertArrayHasKey('features', $array);

        $this->assertEquals(['nodes' => [], 'edges' => []], $array['graph']);
        $envVars = $array['environment_variables'] ?? [];
        $convVars = $array['conversation_variables'] ?? [];
        $this->assertIsArray($envVars);
        $this->assertIsArray($convVars);
        $this->assertCount(1, $envVars);
        $this->assertCount(1, $convVars);
        $this->assertEquals($features, $array['features']);
    }

    public function testToArrayWithEmptyCollections(): void
    {
        $workflow = new Workflow(new Graph());

        $array = $workflow->toArray();

        $this->assertArrayHasKey('graph', $array);
        $this->assertArrayNotHasKey('environment_variables', $array);
        $this->assertArrayNotHasKey('conversation_variables', $array);
        $this->assertArrayNotHasKey('features', $array);
    }

    public function testComplexFeaturesHandling(): void
    {
        $workflow = new Workflow(new Graph());

        // 测试复杂特性对象
        $complexFeature = [
            'file_upload' => [
                'enabled' => true,
                'allowed_file_types' => ['image', 'document'],
                'max_file_size' => 10485760, // 10MB
                'batch_upload' => false,
            ],
            'speech_to_text' => [
                'enabled' => true,
                'languages' => ['en', 'zh'],
                'auto_detection' => true,
            ],
        ];

        $workflow->setFeatures($complexFeature);

        $this->assertEquals($complexFeature, $workflow->getFeatures());
        $this->assertEquals($complexFeature['file_upload'], $workflow->getFeature('file_upload'));

        // 测试单个特性修改
        $workflow->setFeature('speech_to_text', ['enabled' => false]);

        $speechToText = $workflow->getFeature('speech_to_text');
        $this->assertIsArray($speechToText);
        $this->assertFalse($speechToText['enabled'] ?? true);
        $this->assertEquals($complexFeature['file_upload'], $workflow->getFeature('file_upload'));
    }

    public function testVariableCollectionManagement(): void
    {
        $workflow = new Workflow(new Graph());

        // 添加多个环境变量
        $apiKey = new Variable('api_key', 'API Key', 'text-input', true);
        $endpoint = new Variable('endpoint', 'API Endpoint', 'text-input');

        $workflow->addEnvironmentVariable($apiKey)
            ->addEnvironmentVariable($endpoint)
        ;

        $this->assertCount(2, $workflow->getEnvironmentVariables());

        // 添加多个对话变量
        $userId = new Variable('user_id', 'User ID', 'text-input');
        $sessionId = new Variable('session_id', 'Session ID', 'text-input');

        $workflow->addConversationVariable($userId)
            ->addConversationVariable($sessionId)
        ;

        $this->assertCount(2, $workflow->getConversationVariables());

        // 验证变量正确存储
        $envVars = $workflow->getEnvironmentVariables();
        $this->assertContains($apiKey, $envVars);
        $this->assertContains($endpoint, $envVars);

        $convVars = $workflow->getConversationVariables();
        $this->assertContains($userId, $convVars);
        $this->assertContains($sessionId, $convVars);
    }
}
