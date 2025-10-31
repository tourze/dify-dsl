<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Nodes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyDsl\Core\Variable;
use Tourze\DifyDsl\Nodes\StartNode;

/**
 * @internal
 */
#[CoversClass(StartNode::class)]
class StartNodeTest extends TestCase
{
    public function testCreateStartNode(): void
    {
        $node = new StartNode('start_id', 'Start Title', 'Start Description');

        $this->assertEquals('start_id', $node->getId());
        $this->assertEquals('Start Title', $node->getTitle());
        $this->assertEquals('Start Description', $node->getDescription());
        $this->assertEquals('start', $node->getNodeType());
        $this->assertEmpty($node->getVariables());
    }

    public function testCreateStartNodeWithDefaults(): void
    {
        $node = new StartNode('start');

        $this->assertEquals('start', $node->getId());
        $this->assertEquals('开始', $node->getTitle());
        $this->assertEquals('', $node->getDescription());
    }

    public function testCreateFactoryMethod(): void
    {
        $node = StartNode::create();

        $this->assertEquals('start', $node->getId());
        $this->assertEquals('开始', $node->getTitle());
        $this->assertEquals('start', $node->getNodeType());
    }

    public function testCreateFactoryMethodWithCustomId(): void
    {
        $node = StartNode::create('custom_start');

        $this->assertEquals('custom_start', $node->getId());
        $this->assertEquals('开始', $node->getTitle());
    }

    public function testAddVariable(): void
    {
        $node = new StartNode('start');
        $variable = new Variable('user_input', 'User Input', 'text-input', true);

        $result = $node->addVariable($variable);

        $this->assertSame($node, $result); // 测试流式接口
        $this->assertCount(1, $node->getVariables());
        $this->assertContains($variable, $node->getVariables());
    }

    public function testSetVariables(): void
    {
        $node = new StartNode('start');
        $var1 = new Variable('input1', 'Input 1', 'text-input');
        $var2 = new Variable('input2', 'Input 2', 'number');
        $variables = [$var1, $var2];

        $node->setVariables($variables);

        $this->assertEquals($variables, $node->getVariables());
    }

    public function testAddVariableFromArray(): void
    {
        $node = new StartNode('start');

        $result = $node->addVariableFromArray('username', 'text-input', true, 'User Name');

        $this->assertSame($node, $result); // 测试流式接口
        $this->assertCount(1, $node->getVariables());

        $variable = $node->getVariables()[0];
        $this->assertEquals('username', $variable->getVariable());
        $this->assertEquals('User Name', $variable->getLabel());
        $this->assertEquals('text-input', $variable->getType());
        $this->assertTrue($variable->isRequired());
    }

    public function testAddVariableFromArrayWithDefaults(): void
    {
        $node = new StartNode('start');

        $node->addVariableFromArray('simple_var', 'text-input');

        $variable = $node->getVariables()[0];
        $this->assertEquals('simple_var', $variable->getVariable());
        $this->assertEquals('simple_var', $variable->getLabel()); // 默认使用变量名作为标签
        $this->assertFalse($variable->isRequired());
    }

    public function testFromArray(): void
    {
        $data = [
            'id' => 'start_node',
            'type' => 'custom',
            'position' => ['x' => 100, 'y' => 200],
            'data' => [
                'title' => 'Workflow Start',
                'desc' => 'Start of the workflow',
                'variables' => [
                    [
                        'variable' => 'user_query',
                        'label' => 'User Query',
                        'type' => 'text-input',
                        'required' => true,
                    ],
                    [
                        'variable' => 'context',
                        'label' => 'Context',
                        'type' => 'paragraph',
                        'required' => false,
                    ],
                ],
            ],
        ];

        $node = StartNode::fromArray($data);

        $this->assertEquals('start_node', $node->getId());
        $this->assertEquals('Workflow Start', $node->getTitle());
        $this->assertEquals('Start of the workflow', $node->getDescription());
        $this->assertEquals(['x' => 100, 'y' => 200], $node->getPosition());

        $variables = $node->getVariables();
        $this->assertCount(2, $variables);

        $userQuery = $variables[0];
        $this->assertEquals('user_query', $userQuery->getVariable());
        $this->assertEquals('User Query', $userQuery->getLabel());
        $this->assertEquals('text-input', $userQuery->getType());
        $this->assertTrue($userQuery->isRequired());

        $context = $variables[1];
        $this->assertEquals('context', $context->getVariable());
        $this->assertEquals('Context', $context->getLabel());
        $this->assertEquals('paragraph', $context->getType());
        $this->assertFalse($context->isRequired());
    }

    public function testFromArrayWithDefaults(): void
    {
        $data = [
            'id' => 'minimal_start',
        ];

        $node = StartNode::fromArray($data);

        $this->assertEquals('minimal_start', $node->getId());
        $this->assertEquals('开始', $node->getTitle());
        $this->assertEquals('', $node->getDescription());
        $this->assertEmpty($node->getVariables());
    }

    public function testFromArrayWithEmptyData(): void
    {
        $data = [];

        $node = StartNode::fromArray($data);

        $this->assertEquals('', $node->getId());
        $this->assertEquals('开始', $node->getTitle());
        $this->assertEquals('', $node->getDescription());
        $this->assertEmpty($node->getVariables());
    }

    public function testToArray(): void
    {
        $node = new StartNode('workflow_start', 'Start Node', 'Beginning of workflow');
        $node->setPosition(50, 100);

        $variable = new Variable('input_text', 'Input Text', 'text-input', true);
        $node->addVariable($variable);

        $array = $node->toArray();

        $this->assertEquals([
            'id' => 'workflow_start',
            'type' => 'custom',
            'position' => ['x' => 50, 'y' => 100],
            'data' => [
                'type' => 'start',
                'title' => 'Start Node',
                'desc' => 'Beginning of workflow',
                'selected' => false,
                'variables' => [
                    [
                        'variable' => 'input_text',
                        'label' => 'Input Text',
                        'type' => 'text-input',
                        'required' => true,
                    ],
                ],
            ],
        ], $array);
    }

    public function testToArrayWithoutVariables(): void
    {
        $node = new StartNode('simple_start');

        $array = $node->toArray();

        $this->assertIsArray($array['data']);
        $nodeData = $array['data'];
        $this->assertArrayNotHasKey('variables', $nodeData);
    }

    public function testMultipleVariableTypes(): void
    {
        $node = new StartNode('multi_var_start');

        $node->addVariableFromArray('text_input', 'text-input', true, 'Text Input')
            ->addVariableFromArray('number_input', 'number', false, 'Number Input')
            ->addVariableFromArray('select_input', 'select', true, 'Select Input')
            ->addVariableFromArray('file_input', 'file', false, 'File Input')
        ;

        $variables = $node->getVariables();
        $this->assertCount(4, $variables);

        $types = array_map(fn ($var) => $var->getType(), $variables);
        $this->assertEquals(['text-input', 'number', 'select', 'file'], $types);

        $required = array_map(fn ($var) => $var->isRequired(), $variables);
        $this->assertEquals([true, false, true, false], $required);
    }

    public function testVariableObjectAddition(): void
    {
        $node = new StartNode('object_test');

        $var1 = new Variable('var1', 'Variable 1', 'text-input', true, 'First variable');
        $var2 = new Variable('var2', 'Variable 2', 'number', false, 'Second variable', 42);

        $node->addVariable($var1)->addVariable($var2);

        $variables = $node->getVariables();
        $this->assertCount(2, $variables);
        $this->assertSame($var1, $variables[0]);
        $this->assertSame($var2, $variables[1]);
    }

    public function testComplexVariableConfiguration(): void
    {
        $node = new StartNode('complex_start');

        $fileVariable = new Variable(
            variable: 'document_upload',
            label: 'Document Upload',
            type: 'file',
            required: true,
            description: 'Upload a document for processing',
            allowedFileExtensions: ['.pdf', '.doc', '.docx'],
            allowedFileTypes: ['application/pdf', 'application/msword']
        );

        $node->addVariable($fileVariable);

        $variables = $node->getVariables();
        $uploadVar = $variables[0];

        $this->assertEquals('document_upload', $uploadVar->getVariable());
        $this->assertEquals('Document Upload', $uploadVar->getLabel());
        $this->assertEquals('file', $uploadVar->getType());
        $this->assertTrue($uploadVar->isRequired());
        $this->assertEquals('Upload a document for processing', $uploadVar->getDescription());
        $this->assertEquals(['.pdf', '.doc', '.docx'], $uploadVar->getAllowedFileExtensions());
        $this->assertEquals(['application/pdf', 'application/msword'], $uploadVar->getAllowedFileTypes());
    }

    public function testRoundTripSerialization(): void
    {
        $originalData = [
            'id' => 'roundtrip_start',
            'type' => 'custom',
            'position' => ['x' => 200, 'y' => 300],
            'data' => [
                'title' => 'Round Trip Start',
                'desc' => 'Testing serialization',
                'variables' => [
                    [
                        'variable' => 'test_input',
                        'label' => 'Test Input',
                        'type' => 'text-input',
                        'required' => true,
                        'description' => 'A test input field',
                    ],
                ],
            ],
        ];

        $node = StartNode::fromArray($originalData);
        $serialized = $node->toArray();

        // 验证核心属性保持一致
        $this->assertEquals($originalData['id'], $serialized['id']);
        $this->assertEquals($originalData['position'], $serialized['position']);
        $this->assertIsArray($serialized['data']);
        $this->assertIsString($serialized['data']['title']);
        $this->assertIsString($serialized['data']['desc']);
        $this->assertEquals($originalData['data']['title'], $serialized['data']['title']);
        $this->assertEquals($originalData['data']['desc'], $serialized['data']['desc']);

        // 验证变量数据
        $this->assertIsArray($serialized['data']['variables']);
        $this->assertIsArray($serialized['data']['variables'][0]);
        $originalVar = $originalData['data']['variables'][0];
        $serializedVar = $serialized['data']['variables'][0];
        $this->assertIsString($serializedVar['variable']);
        $this->assertIsString($serializedVar['label']);
        $this->assertIsString($serializedVar['type']);
        $this->assertIsBool($serializedVar['required']);
        $this->assertIsString($serializedVar['description']);
        $this->assertEquals($originalVar['variable'], $serializedVar['variable']);
        $this->assertEquals($originalVar['label'], $serializedVar['label']);
        $this->assertEquals($originalVar['type'], $serializedVar['type']);
        $this->assertEquals($originalVar['required'], $serializedVar['required']);
        $this->assertEquals($originalVar['description'], $serializedVar['description']);
    }

    public function testFluentVariableAddition(): void
    {
        $node = StartNode::create('fluent_start');

        $result = $node->addVariableFromArray('input1', 'text-input')
            ->addVariableFromArray('input2', 'number')
            ->addVariableFromArray('input3', 'select')
        ;

        $this->assertSame($node, $result);
        $this->assertCount(3, $node->getVariables());
    }

    public function testInheritedProperties(): void
    {
        $node = StartNode::create('inherited_test');

        $node->setTitle('Custom Start Title');
        $node->setDescription('Custom Description');
        $node->setPosition(150, 250);
        $node->setSelected(true);

        $this->assertEquals('Custom Start Title', $node->getTitle());
        $this->assertEquals('Custom Description', $node->getDescription());
        $this->assertEquals(['x' => 150, 'y' => 250], $node->getPosition());

        $array = $node->toArray();
        $this->assertTrue($array['selected']);
    }
}
