<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Nodes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyDsl\Nodes\EndNode;

/**
 * @internal
 */
#[CoversClass(EndNode::class)]
class EndNodeTest extends TestCase
{
    public function testCreateEndNode(): void
    {
        $node = new EndNode('end_id', 'End Title', 'End Description');

        $this->assertEquals('end_id', $node->getId());
        $this->assertEquals('End Title', $node->getTitle());
        $this->assertEquals('End Description', $node->getDescription());
        $this->assertEquals('end', $node->getNodeType());
        $this->assertEmpty($node->getOutputs());
    }

    public function testCreateEndNodeWithDefaults(): void
    {
        $node = new EndNode('end');

        $this->assertEquals('end', $node->getId());
        $this->assertEquals('结束', $node->getTitle());
        $this->assertEquals('', $node->getDescription());
    }

    public function testCreateFactoryMethod(): void
    {
        $node = EndNode::create();

        $this->assertEquals('end', $node->getId());
        $this->assertEquals('结束', $node->getTitle());
        $this->assertEquals('end', $node->getNodeType());
    }

    public function testCreateFactoryMethodWithCustomId(): void
    {
        $node = EndNode::create('custom_end');

        $this->assertEquals('custom_end', $node->getId());
        $this->assertEquals('结束', $node->getTitle());
    }

    public function testSetOutputs(): void
    {
        $node = new EndNode('end');
        $outputs = [
            ['variable' => 'result', 'value_selector' => ['node_id', 'output']],
            ['variable' => 'status', 'value_selector' => ['process', 'status']],
        ];

        $node->setOutputs($outputs);

        // setOutputs() 返回 void，无需测试流式接口
        $this->assertEquals($outputs, $node->getOutputs());
    }

    public function testAddOutput(): void
    {
        $node = new EndNode('end');

        $result = $node->addOutput('final_result', ['llm_node', 'text']);

        $this->assertSame($node, $result); // 测试流式接口
        $this->assertCount(1, $node->getOutputs());

        $output = $node->getOutputs()[0];
        $this->assertEquals('final_result', $output['variable']);
        $this->assertEquals(['llm_node', 'text'], $output['value_selector']);
    }

    public function testAddMultipleOutputs(): void
    {
        $node = new EndNode('end');

        $node->addOutput('result', ['node1', 'output'])
            ->addOutput('metadata', ['node2', 'meta'])
            ->addOutput('status', ['node3', 'status'])
        ;

        $outputs = $node->getOutputs();
        $this->assertCount(3, $outputs);

        $this->assertEquals('result', $outputs[0]['variable']);
        $this->assertEquals(['node1', 'output'], $outputs[0]['value_selector']);

        $this->assertEquals('metadata', $outputs[1]['variable']);
        $this->assertEquals(['node2', 'meta'], $outputs[1]['value_selector']);

        $this->assertEquals('status', $outputs[2]['variable']);
        $this->assertEquals(['node3', 'status'], $outputs[2]['value_selector']);
    }

    public function testFromArray(): void
    {
        $data = [
            'id' => 'end_node',
            'type' => 'custom',
            'position' => ['x' => 500, 'y' => 300],
            'data' => [
                'title' => 'Workflow End',
                'desc' => 'End of the workflow',
                'outputs' => [
                    [
                        'variable' => 'final_answer',
                        'value_selector' => ['llm_processor', 'text'],
                    ],
                    [
                        'variable' => 'confidence_score',
                        'value_selector' => ['evaluator', 'confidence'],
                    ],
                ],
            ],
        ];

        $node = EndNode::fromArray($data);

        $this->assertEquals('end_node', $node->getId());
        $this->assertEquals('Workflow End', $node->getTitle());
        $this->assertEquals('End of the workflow', $node->getDescription());
        $this->assertEquals(['x' => 500, 'y' => 300], $node->getPosition());

        $outputs = $node->getOutputs();
        $this->assertCount(2, $outputs);

        $this->assertEquals('final_answer', $outputs[0]['variable']);
        $this->assertEquals(['llm_processor', 'text'], $outputs[0]['value_selector']);

        $this->assertEquals('confidence_score', $outputs[1]['variable']);
        $this->assertEquals(['evaluator', 'confidence'], $outputs[1]['value_selector']);
    }

    public function testFromArrayWithDefaults(): void
    {
        $data = [
            'id' => 'minimal_end',
        ];

        $node = EndNode::fromArray($data);

        $this->assertEquals('minimal_end', $node->getId());
        $this->assertEquals('结束', $node->getTitle());
        $this->assertEquals('', $node->getDescription());
        $this->assertEmpty($node->getOutputs());
    }

    public function testFromArrayWithEmptyData(): void
    {
        $data = [];

        $node = EndNode::fromArray($data);

        $this->assertEquals('', $node->getId());
        $this->assertEquals('结束', $node->getTitle());
        $this->assertEquals('', $node->getDescription());
        $this->assertEmpty($node->getOutputs());
    }

    public function testToArray(): void
    {
        $node = new EndNode('workflow_end', 'End Node', 'Final step');
        $node->setPosition(400, 200);
        $node->addOutput('result_text', ['llm', 'output'])
            ->addOutput('processing_time', ['timer', 'duration'])
        ;

        $array = $node->toArray();

        $this->assertEquals([
            'id' => 'workflow_end',
            'type' => 'custom',
            'position' => ['x' => 400, 'y' => 200],
            'data' => [
                'type' => 'end',
                'title' => 'End Node',
                'desc' => 'Final step',
                'selected' => false,
                'outputs' => [
                    [
                        'variable' => 'result_text',
                        'value_selector' => ['llm', 'output'],
                    ],
                    [
                        'variable' => 'processing_time',
                        'value_selector' => ['timer', 'duration'],
                    ],
                ],
            ],
        ], $array);
    }

    public function testToArrayWithoutOutputs(): void
    {
        $node = new EndNode('simple_end');

        $array = $node->toArray();

        $this->assertIsArray($array['data']);
        $nodeData = $array['data'];
        $this->assertArrayNotHasKey('outputs', $nodeData);
    }

    public function testComplexOutputConfiguration(): void
    {
        $node = new EndNode('complex_end');

        // 复杂的输出选择器配置
        $node->addOutput('structured_result', ['processor', 'structured_output', 'data'])
            ->addOutput('error_info', ['error_handler', 'error_details'])
            ->addOutput('execution_metadata', ['workflow', 'metadata', 'execution_info'])
        ;

        $outputs = $node->getOutputs();
        $this->assertCount(3, $outputs);

        // 验证嵌套的value_selector
        $this->assertEquals(['processor', 'structured_output', 'data'], $outputs[0]['value_selector']);
        $this->assertEquals(['error_handler', 'error_details'], $outputs[1]['value_selector']);
        $this->assertEquals(['workflow', 'metadata', 'execution_info'], $outputs[2]['value_selector']);
    }

    public function testOutputReplacement(): void
    {
        $node = new EndNode('replacement_test');

        $originalOutputs = [
            ['variable' => 'old_result', 'value_selector' => ['old_node', 'output']],
        ];

        $newOutputs = [
            ['variable' => 'new_result', 'value_selector' => ['new_node', 'output']],
            ['variable' => 'additional_data', 'value_selector' => ['extra_node', 'data']],
        ];

        $node->setOutputs($originalOutputs);
        $this->assertCount(1, $node->getOutputs());

        $node->setOutputs($newOutputs);
        $this->assertCount(2, $node->getOutputs());
        $this->assertEquals('new_result', $node->getOutputs()[0]['variable']);
        $this->assertEquals('additional_data', $node->getOutputs()[1]['variable']);
    }

    public function testRoundTripSerialization(): void
    {
        $originalData = [
            'id' => 'roundtrip_end',
            'type' => 'custom',
            'position' => ['x' => 300, 'y' => 400],
            'selected' => true,
            'data' => [
                'title' => 'Round Trip End',
                'desc' => 'Testing serialization',
                'outputs' => [
                    [
                        'variable' => 'test_output',
                        'value_selector' => ['test_node', 'result'],
                    ],
                ],
            ],
        ];

        $node = EndNode::fromArray($originalData);
        $serialized = $node->toArray();

        // 验证核心属性保持一致
        $this->assertEquals($originalData['id'], $serialized['id']);
        $this->assertEquals($originalData['position'], $serialized['position']);
        $this->assertEquals($originalData['selected'], $serialized['selected']);
        $this->assertIsArray($serialized['data']);
        $this->assertIsString($serialized['data']['title']);
        $this->assertIsString($serialized['data']['desc']);
        $this->assertEquals($originalData['data']['title'], $serialized['data']['title']);
        $this->assertEquals($originalData['data']['desc'], $serialized['data']['desc']);

        // 验证输出数据
        $this->assertIsArray($serialized['data']['outputs']);
        $this->assertIsArray($serialized['data']['outputs'][0]);
        $originalOutput = $originalData['data']['outputs'][0];
        $serializedOutput = $serialized['data']['outputs'][0];
        $this->assertIsString($serializedOutput['variable']);
        $this->assertIsArray($serializedOutput['value_selector']);
        $this->assertEquals($originalOutput['variable'], $serializedOutput['variable']);
        $this->assertEquals($originalOutput['value_selector'], $serializedOutput['value_selector']);
    }

    public function testFluentOutputConfiguration(): void
    {
        $node = EndNode::create('fluent_end');

        $node->addOutput('output1', ['node1', 'data'])
            ->addOutput('output2', ['node2', 'result'])
        ;
        $node->setTitle('Fluent End Node');

        // setTitle() 返回 void，拆分链式调用
        $this->assertCount(2, $node->getOutputs());
        $this->assertEquals('Fluent End Node', $node->getTitle());
    }

    public function testInheritedProperties(): void
    {
        $node = EndNode::create('inherited_test');

        $node->setTitle('Custom End Title');
        $node->setDescription('Custom Description');
        $node->setPosition(250, 350);
        $node->setSelected(true);

        $this->assertEquals('Custom End Title', $node->getTitle());
        $this->assertEquals('Custom Description', $node->getDescription());
        $this->assertEquals(['x' => 250, 'y' => 350], $node->getPosition());

        $array = $node->toArray();
        $this->assertTrue($array['selected']);
    }

    public function testEmptyOutputConfiguration(): void
    {
        $node = new EndNode('empty_outputs');

        // 设置空输出数组
        $node->setOutputs([]);

        $this->assertEmpty($node->getOutputs());

        $array = $node->toArray();
        $this->assertIsArray($array['data']);
        $this->assertArrayNotHasKey('outputs', $array['data']);
    }

    public function testDynamicOutputManagement(): void
    {
        $node = new EndNode('dynamic_test');

        // 动态添加输出
        $node->addOutput('dynamic1', ['src1', 'out1']);
        $this->assertCount(1, $node->getOutputs());

        $node->addOutput('dynamic2', ['src2', 'out2']);
        $this->assertCount(2, $node->getOutputs());

        // 替换所有输出
        $node->setOutputs([
            ['variable' => 'replaced', 'value_selector' => ['new_src', 'new_out']],
        ]);
        $this->assertCount(1, $node->getOutputs());
        $this->assertEquals('replaced', $node->getOutputs()[0]['variable']);
    }
}
