<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Nodes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyDsl\Nodes\AnswerNode;

// 直接加载依赖文件
require_once __DIR__ . '/../../src/Nodes/AbstractNode.php';
require_once __DIR__ . '/../../src/Nodes/AnswerNode.php';

/**
 * @internal
 */
#[CoversClass(AnswerNode::class)]
class AnswerNodeTest extends TestCase
{
    public function testCreateAnswerNode(): void
    {
        $node = new AnswerNode('answer_id', 'Answer Title', 'Answer Description');

        $this->assertEquals('answer_id', $node->getId());
        $this->assertEquals('Answer Title', $node->getTitle());
        $this->assertEquals('Answer Description', $node->getDescription());
        $this->assertEquals('answer', $node->getNodeType());
        $this->assertEquals('', $node->getAnswer());
        $this->assertEmpty($node->getVariables());
    }

    public function testCreateAnswerNodeWithDefaults(): void
    {
        $node = new AnswerNode('answer');

        $this->assertEquals('answer', $node->getId());
        $this->assertEquals('直接回复', $node->getTitle());
        $this->assertEquals('', $node->getDescription());
    }

    public function testCreateFactoryMethod(): void
    {
        $node = AnswerNode::create();

        $this->assertEquals('answer', $node->getId());
        $this->assertEquals('直接回复', $node->getTitle());
        $this->assertEquals('answer', $node->getNodeType());
    }

    public function testCreateFactoryMethodWithAnswer(): void
    {
        $answer = 'Hello, this is the answer!';
        $node = AnswerNode::create('custom_answer', $answer);

        $this->assertEquals('custom_answer', $node->getId());
        $this->assertEquals($answer, $node->getAnswer());
    }

    public function testSetAnswer(): void
    {
        $node = new AnswerNode('answer');
        $answer = 'This is a test answer with variables: {{user_input}}';

        $node->setAnswer($answer);

        // 验证设置成功
        $this->assertEquals($answer, $node->getAnswer());
    }

    public function testSetVariables(): void
    {
        $node = new AnswerNode('answer');
        $variables = [
            'user_input' => '{{start.user_input}}',
            'llm_response' => '{{llm.text}}',
            'metadata' => ['type' => 'response', 'timestamp' => '{{now}}'],
        ];

        $node->setVariables($variables);

        // 验证设置成功
        $this->assertEquals($variables, $node->getVariables());
    }

    public function testFromArray(): void
    {
        $data = [
            'id' => 'answer_node',
            'type' => 'custom',
            'position' => ['x' => 300, 'y' => 200],
            'data' => [
                'title' => 'Final Answer',
                'desc' => 'Provides the final answer to user',
                'answer' => 'Based on your input "{{user_query}}", here is the result: {{llm_result}}',
                'variables' => [
                    'user_query' => '{{start.query}}',
                    'llm_result' => '{{llm.text}}',
                    'confidence' => '{{llm.metadata.confidence}}',
                ],
            ],
        ];

        $node = AnswerNode::fromArray($data);

        $this->assertEquals('answer_node', $node->getId());
        $this->assertEquals('Final Answer', $node->getTitle());
        $this->assertEquals('Provides the final answer to user', $node->getDescription());
        $this->assertEquals(['x' => 300, 'y' => 200], $node->getPosition());

        $this->assertEquals('Based on your input "{{user_query}}", here is the result: {{llm_result}}', $node->getAnswer());

        $variables = $node->getVariables();
        $this->assertEquals('{{start.query}}', $variables['user_query']);
        $this->assertEquals('{{llm.text}}', $variables['llm_result']);
        $this->assertEquals('{{llm.metadata.confidence}}', $variables['confidence']);
    }

    public function testFromArrayWithDefaults(): void
    {
        $data = [
            'id' => 'minimal_answer',
        ];

        $node = AnswerNode::fromArray($data);

        $this->assertEquals('minimal_answer', $node->getId());
        $this->assertEquals('直接回复', $node->getTitle());
        $this->assertEquals('', $node->getDescription());
        $this->assertEquals('', $node->getAnswer());
        $this->assertEmpty($node->getVariables());
    }

    public function testToArray(): void
    {
        $node = new AnswerNode('response_node', 'Response Node', 'Generates response');
        $node->setPosition(250, 300);
        $node->setAnswer('Hello {{user_name}}, your query "{{query}}" has been processed: {{result}}');
        $node->setVariables([
            'user_name' => '{{start.user_name}}',
            'query' => '{{start.query}}',
            'result' => '{{processor.output}}',
        ]);

        $array = $node->toArray();

        $this->assertEquals([
            'id' => 'response_node',
            'type' => 'custom',
            'position' => ['x' => 250, 'y' => 300],
            'data' => [
                'type' => 'answer',
                'title' => 'Response Node',
                'desc' => 'Generates response',
                'selected' => false,
                'answer' => 'Hello {{user_name}}, your query "{{query}}" has been processed: {{result}}',
                'variables' => [
                    'user_name' => '{{start.user_name}}',
                    'query' => '{{start.query}}',
                    'result' => '{{processor.output}}',
                ],
            ],
        ], $array);
    }

    public function testToArrayWithoutAnswerAndVariables(): void
    {
        $node = new AnswerNode('simple_answer');

        $array = $node->toArray();

        $this->assertIsArray($array['data']);
        $nodeData = $array['data'];
        $this->assertArrayNotHasKey('answer', $nodeData);
        $this->assertArrayNotHasKey('variables', $nodeData);
    }

    public function testVariableTemplating(): void
    {
        $node = new AnswerNode('template_test');

        $answer = 'Welcome {{user.name}}! Your request for "{{request.topic}}" has been processed. Status: {{processing.status}}';
        $variables = [
            'user.name' => '{{start.user_input.name}}',
            'request.topic' => '{{start.user_input.topic}}',
            'processing.status' => '{{llm.metadata.status}}',
        ];

        $node->setAnswer($answer);
        $node->setVariables($variables);

        $this->assertEquals($answer, $node->getAnswer());
        $this->assertEquals($variables, $node->getVariables());
    }

    public function testEmptyAnswerHandling(): void
    {
        $node = new AnswerNode('empty_test');

        $this->assertEquals('', $node->getAnswer());

        $node->setAnswer('');
        $this->assertEquals('', $node->getAnswer());

        $array = $node->toArray();
        $this->assertIsArray($array['data']);
        $this->assertArrayNotHasKey('answer', $array['data']);
    }

    public function testFluentInterface(): void
    {
        $node = AnswerNode::create('fluent_test');

        $node->setAnswer('Fluent answer: {{result}}');
        $node->setVariables(['result' => '{{llm.text}}']);
        $node->setTitle('Fluent Answer Node');
        $node->setDescription('Testing fluent interface');

        // 验证所有设置都成功应用
        $this->assertEquals('Fluent answer: {{result}}', $node->getAnswer());
        $this->assertEquals(['result' => '{{llm.text}}'], $node->getVariables());
        $this->assertEquals('Fluent Answer Node', $node->getTitle());
        $this->assertEquals('Testing fluent interface', $node->getDescription());
    }

    public function testRoundTripSerialization(): void
    {
        $originalData = [
            'id' => 'roundtrip_answer',
            'position' => ['x' => 400, 'y' => 500],
            'data' => [
                'title' => 'Round Trip Answer',
                'answer' => 'Serialization test: {{data}}',
                'variables' => ['data' => '{{source.output}}'],
            ],
        ];

        $node = AnswerNode::fromArray($originalData);
        $serialized = $node->toArray();

        // 核心属性应该保持一致
        $this->assertEquals($originalData['id'], $serialized['id']);
        $this->assertEquals($originalData['position'], $serialized['position']);
        $this->assertIsArray($serialized['data']);
        $this->assertIsString($serialized['data']['title']);
        $this->assertIsString($serialized['data']['answer']);
        $this->assertIsArray($serialized['data']['variables']);
        $this->assertEquals($originalData['data']['title'], $serialized['data']['title']);
        $this->assertEquals($originalData['data']['answer'], $serialized['data']['answer']);
        $this->assertEquals($originalData['data']['variables'], $serialized['data']['variables']);
    }

    public function testComplexVariableStructures(): void
    {
        $node = new AnswerNode('complex_vars');

        $complexVariables = [
            'simple_string' => '{{llm.text}}',
            'nested_object' => [
                'user' => '{{start.user}}',
                'metadata' => [
                    'timestamp' => '{{now}}',
                    'confidence' => '{{llm.confidence}}',
                ],
            ],
            'array_values' => ['{{item1}}', '{{item2}}', '{{item3}}'],
            'conditional' => '{{if condition}}{{value1}}{{else}}{{value2}}{{endif}}',
        ];

        $node->setVariables($complexVariables);

        $this->assertEquals($complexVariables, $node->getVariables());

        $array = $node->toArray();
        $this->assertIsArray($array['data']);
        $this->assertIsArray($array['data']['variables']);
        $this->assertEquals($complexVariables, $array['data']['variables']);
    }
}
