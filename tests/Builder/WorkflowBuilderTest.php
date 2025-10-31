<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Builder;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyDsl\Builder\WorkflowBuilder;
use Tourze\DifyDsl\Core\App;
use Tourze\DifyDsl\Core\Graph;
use Tourze\DifyDsl\Core\Variable;
use Tourze\DifyDsl\Nodes\AnswerNode;
use Tourze\DifyDsl\Nodes\CodeNode;
use Tourze\DifyDsl\Nodes\EndNode;
use Tourze\DifyDsl\Nodes\LLMNode;
use Tourze\DifyDsl\Nodes\StartNode;
use Tourze\DifyDsl\Nodes\ToolNode;

/**
 * @internal
 */
#[CoversClass(WorkflowBuilder::class)]
class WorkflowBuilderTest extends TestCase
{
    public function testCreateBuilder(): void
    {
        $builder = new WorkflowBuilder();

        $this->assertInstanceOf(WorkflowBuilder::class, $builder);
    }

    public function testCreateStaticFactory(): void
    {
        $builder = WorkflowBuilder::create();

        $this->assertInstanceOf(WorkflowBuilder::class, $builder);
    }

    public function testSetBasicProperties(): void
    {
        $builder = new WorkflowBuilder();
        $builder->setName('Test Workflow');
        $builder->setDescription('A test workflow description');
        $builder->setMode('chat');
        $builder->setIcon('🔥', '#FF0000');

        $app = $builder->build();

        $this->assertEquals('Test Workflow', $app->getName());
        $this->assertEquals('A test workflow description', $app->getDescription());
        $this->assertEquals('chat', $app->getMode());
        $this->assertEquals('🔥', $app->getIcon());
        $this->assertEquals('#FF0000', $app->getIconBackground());
    }

    public function testAddEnvironmentVariable(): void
    {
        $builder = new WorkflowBuilder();

        $result = $builder->addEnvironmentVariable('api_key', 'string', 'default_key');

        $this->assertSame($builder, $result); // 测试流式接口

        $app = $builder->build();
        $envVars = $app->getWorkflow()->getEnvironmentVariables();

        $this->assertCount(1, $envVars);
        $this->assertEquals('api_key', $envVars[0]->getVariable());
        $this->assertEquals('api_key', $envVars[0]->getLabel());
        $this->assertEquals('string', $envVars[0]->getType());
        $this->assertEquals('default_key', $envVars[0]->getDefaultValue());
    }

    public function testAddConversationVariable(): void
    {
        $builder = new WorkflowBuilder();

        $result = $builder->addConversationVariable('user_name', 'text-input', 'Anonymous');

        $this->assertSame($builder, $result); // 测试流式接口

        $app = $builder->build();
        $convVars = $app->getWorkflow()->getConversationVariables();

        $this->assertCount(1, $convVars);
        $this->assertEquals('user_name', $convVars[0]->getVariable());
        $this->assertEquals('user_name', $convVars[0]->getLabel());
        $this->assertEquals('text-input', $convVars[0]->getType());
        $this->assertEquals('Anonymous', $convVars[0]->getDefaultValue());
    }

    public function testEnableFileUpload(): void
    {
        $builder = new WorkflowBuilder();

        $result = $builder->enableFileUpload(['image', 'document'], 10);

        $this->assertSame($builder, $result); // 测试流式接口

        $app = $builder->build();
        $features = $app->getWorkflow()->getFeatures();

        $this->assertArrayHasKey('file_upload', $features);
        $fileUpload = $features['file_upload'] ?? [];
        $this->assertIsArray($fileUpload);
        $this->assertTrue($fileUpload['enabled'] ?? false);
        $this->assertEquals(['image', 'document'], $fileUpload['allowed_file_types'] ?? []);
        $this->assertEquals(10, $fileUpload['number_limits'] ?? 0);
    }

    public function testEnableFileUploadWithDefaults(): void
    {
        $builder = new WorkflowBuilder();

        $builder->enableFileUpload();

        $app = $builder->build();
        $features = $app->getWorkflow()->getFeatures();

        $this->assertArrayHasKey('file_upload', $features);
        $fileUpload = $features['file_upload'] ?? [];
        $this->assertIsArray($fileUpload);
        $this->assertEquals(['image'], $fileUpload['allowed_file_types'] ?? []);
        $this->assertEquals(5, $fileUpload['number_limits'] ?? 0);
    }

    public function testSetOpeningStatement(): void
    {
        $builder = new WorkflowBuilder();

        $result = $builder->setOpeningStatement('Hello! How can I help you?');

        $this->assertSame($builder, $result); // 测试流式接口

        $app = $builder->build();
        $features = $app->getWorkflow()->getFeatures();

        $this->assertEquals('Hello! How can I help you?', $features['opening_statement']);
    }

    public function testAddStartNode(): void
    {
        $builder = new WorkflowBuilder();

        $result = $builder->addStartNode();

        $this->assertSame($builder, $result); // 测试流式接口

        $app = $builder->build();
        $nodes = $app->getWorkflow()->getGraph()->getNodes();

        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(StartNode::class, $nodes[0]);
        $this->assertEquals('start', $nodes[0]->getId());
    }

    public function testAddStartNodeWithConfigurator(): void
    {
        $builder = new WorkflowBuilder();

        $builder->addStartNode(function (StartNode $node) {
            $node->setTitle('Custom Start');
        });

        $app = $builder->build();
        $startNode = $app->getWorkflow()->getGraph()->getNode('start');

        $this->assertInstanceOf(StartNode::class, $startNode);
        $this->assertEquals('Custom Start', $startNode->getTitle());
    }

    public function testAddLLMNode(): void
    {
        $builder = new WorkflowBuilder();

        $result = $builder->addLLMNode('custom_llm');

        $this->assertSame($builder, $result); // 测试流式接口

        $app = $builder->build();
        $nodes = $app->getWorkflow()->getGraph()->getNodes();

        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(LLMNode::class, $nodes[0]);
        $this->assertEquals('custom_llm', $nodes[0]->getId());
    }

    public function testAddLLMNodeWithAutoId(): void
    {
        $builder = new WorkflowBuilder();

        $builder->addLLMNode();

        $app = $builder->build();
        $nodes = $app->getWorkflow()->getGraph()->getNodes();

        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(LLMNode::class, $nodes[0]);
        $this->assertStringStartsWith('llm_', $nodes[0]->getId());
    }

    public function testAddToolNode(): void
    {
        $builder = new WorkflowBuilder();

        $result = $builder->addToolNode('search_tool');

        $this->assertSame($builder, $result); // 测试流式接口

        $app = $builder->build();
        $nodes = $app->getWorkflow()->getGraph()->getNodes();

        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(ToolNode::class, $nodes[0]);
        $this->assertEquals('search_tool', $nodes[0]->getId());
    }

    public function testAddCodeNode(): void
    {
        $builder = new WorkflowBuilder();

        $result = $builder->addCodeNode('python_script');

        $this->assertSame($builder, $result); // 测试流式接口

        $app = $builder->build();
        $nodes = $app->getWorkflow()->getGraph()->getNodes();

        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(CodeNode::class, $nodes[0]);
        $this->assertEquals('python_script', $nodes[0]->getId());
    }

    public function testAddEndNode(): void
    {
        $builder = new WorkflowBuilder();

        $result = $builder->addEndNode();

        $this->assertSame($builder, $result); // 测试流式接口

        $app = $builder->build();
        $nodes = $app->getWorkflow()->getGraph()->getNodes();

        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(EndNode::class, $nodes[0]);
        $this->assertEquals('end', $nodes[0]->getId());
    }

    public function testAddAnswerNode(): void
    {
        $builder = new WorkflowBuilder();

        $result = $builder->addAnswerNode('final_answer');

        $this->assertSame($builder, $result); // 测试流式接口

        $app = $builder->build();
        $nodes = $app->getWorkflow()->getGraph()->getNodes();

        $this->assertCount(1, $nodes);
        $this->assertInstanceOf(AnswerNode::class, $nodes[0]);
        $this->assertEquals('final_answer', $nodes[0]->getId());
    }

    public function testAddCustomNode(): void
    {
        $builder = new WorkflowBuilder();
        $customNode = new LLMNode('custom', 'Custom Node', 'Custom description');

        $result = $builder->addCustomNode($customNode);

        $this->assertSame($builder, $result); // 测试流式接口

        $app = $builder->build();
        $nodes = $app->getWorkflow()->getGraph()->getNodes();

        $this->assertCount(1, $nodes);
        $this->assertSame($customNode, $nodes[0]);
    }

    public function testConnectNodes(): void
    {
        $builder = new WorkflowBuilder();

        $result = $builder->connectNodes('node1', 'node2');

        $this->assertSame($builder, $result); // 测试流式接口

        $app = $builder->build();
        $edges = $app->getWorkflow()->getGraph()->getEdges();

        $this->assertCount(1, $edges);
        $this->assertEquals('node1', $edges[0]->getSource());
        $this->assertEquals('node2', $edges[0]->getTarget());
    }

    public function testNodeChaining(): void
    {
        $builder = new WorkflowBuilder();

        $builder->addStartNode()
            ->addLLMNode('llm1')
            ->addToolNode('tool1')
            ->addEndNode()
        ;

        $app = $builder->build();
        $graph = $app->getWorkflow()->getGraph();

        // 验证节点数量
        $this->assertCount(4, $graph->getNodes());

        // 验证边的连接
        $edges = $graph->getEdges();
        $this->assertCount(3, $edges);

        // 验证连接顺序
        $edgeConnections = array_map(fn ($edge) => $edge->getSource() . '->' . $edge->getTarget(), $edges);

        $this->assertContains('start->llm1', $edgeConnections);
        $this->assertContains('llm1->tool1', $edgeConnections);
        $this->assertContains('tool1->end', $edgeConnections);
    }

    public function testComplexWorkflowBuilding(): void
    {
        $builder = WorkflowBuilder::create();

        $builder->setName('Complex Workflow');
        $builder->setDescription('A complex test workflow');
        $builder->setMode('chat');
        $builder->setIcon('⚡', '#BLUE');
        $app = $builder
            ->addEnvironmentVariable('api_key', 'string', 'test_key')
            ->addConversationVariable('user_id', 'text-input')
            ->enableFileUpload(['image', 'pdf'], 3)
            ->setOpeningStatement('Welcome to the complex workflow!')
            ->addStartNode()
            ->addLLMNode('main_llm')
            ->addToolNode('search')
            ->addCodeNode('processor')
            ->addAnswerNode('response')
            ->build()
        ;

        // 验证应用属性
        $this->assertEquals('Complex Workflow', $app->getName());
        $this->assertEquals('A complex test workflow', $app->getDescription());
        $this->assertEquals('chat', $app->getMode());
        $this->assertEquals('⚡', $app->getIcon());
        $this->assertEquals('#BLUE', $app->getIconBackground());

        // 验证工作流
        $workflow = $app->getWorkflow();
        $this->assertCount(1, $workflow->getEnvironmentVariables());
        $this->assertCount(1, $workflow->getConversationVariables());

        $features = $workflow->getFeatures();
        $this->assertEquals('Welcome to the complex workflow!', $features['opening_statement']);
        $fileUpload = $features['file_upload'] ?? [];
        $this->assertIsArray($fileUpload);
        $this->assertTrue($fileUpload['enabled'] ?? false);

        // 验证图结构
        $graph = $workflow->getGraph();
        $this->assertCount(5, $graph->getNodes());
        $this->assertCount(4, $graph->getEdges());
    }

    public function testBuildWithoutNodes(): void
    {
        $builder = new WorkflowBuilder();

        $app = $builder->build();

        $this->assertInstanceOf(App::class, $app);
        $this->assertEmpty($app->getWorkflow()->getGraph()->getNodes());
        $this->assertEmpty($app->getWorkflow()->getGraph()->getEdges());
    }

    public function testNodeConfiguratorExecution(): void
    {
        $builder = new WorkflowBuilder();
        $configuratorCalled = false;

        $builder->addLLMNode('test_llm', function (LLMNode $node) use (&$configuratorCalled) {
            $configuratorCalled = true;
            $node->setTitle('Configured LLM');
        });

        $this->assertTrue($configuratorCalled);

        $app = $builder->build();
        $llmNode = $app->getWorkflow()->getGraph()->getNode('test_llm');

        $this->assertNotNull($llmNode);
        $this->assertEquals('Configured LLM', $llmNode->getTitle());
    }

    public function testMultipleVariableTypes(): void
    {
        $builder = new WorkflowBuilder();

        $builder->addEnvironmentVariable('string_var', 'string', 'default')
            ->addEnvironmentVariable('number_var', 'number', 42)
            ->addConversationVariable('bool_var', 'boolean', true)
            ->addConversationVariable('array_var', 'array', ['item1', 'item2'])
        ;

        $app = $builder->build();
        $workflow = $app->getWorkflow();

        $envVars = $workflow->getEnvironmentVariables();
        $convVars = $workflow->getConversationVariables();

        $this->assertCount(2, $envVars);
        $this->assertCount(2, $convVars);

        // 验证变量类型和默认值
        $this->assertEquals('string', $envVars[0]->getType());
        $this->assertEquals('default', $envVars[0]->getDefaultValue());

        $this->assertEquals('number', $envVars[1]->getType());
        $this->assertEquals(42, $envVars[1]->getDefaultValue());

        $this->assertEquals('boolean', $convVars[0]->getType());
        $this->assertTrue($convVars[0]->getDefaultValue());
    }

    public function testFluentInterface(): void
    {
        $builder = new WorkflowBuilder();

        // 测试所有方法都返回 builder 实例
        $result = $builder->addEnvironmentVariable('test', 'string')
            ->addConversationVariable('test2', 'number')
            ->enableFileUpload()
            ->setOpeningStatement('Hello')
            ->addStartNode()
            ->addLLMNode()
            ->addEndNode()
            ->connectNodes('custom1', 'custom2')
        ;

        $this->assertSame($builder, $result);
    }

    public function testDefaultValues(): void
    {
        $builder = new WorkflowBuilder();
        $app = $builder->build();

        // 验证默认值
        $this->assertEquals('', $app->getName());
        $this->assertEquals('', $app->getDescription());
        $this->assertEquals('workflow', $app->getMode());
        $this->assertEquals('🤖', $app->getIcon());
        $this->assertEquals('#FFEAD5', $app->getIconBackground());
        $this->assertEmpty($app->getWorkflow()->getEnvironmentVariables());
        $this->assertEmpty($app->getWorkflow()->getConversationVariables());
        $this->assertEmpty($app->getWorkflow()->getFeatures());
    }
}
