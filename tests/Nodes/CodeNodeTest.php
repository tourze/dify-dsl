<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Nodes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyDsl\Nodes\CodeNode;

// 直接加载依赖文件
require_once __DIR__ . '/../../src/Nodes/AbstractNode.php';
require_once __DIR__ . '/../../src/Nodes/CodeNode.php';

/**
 * @internal
 */
#[CoversClass(CodeNode::class)]
final class CodeNodeTest extends TestCase
{
    public function testFromArray(): void
    {
        $data = [
            'id' => 'code-1',
            'data' => [
                'title' => 'Test Code',
                'desc' => 'Test Description',
                'code' => 'print("hello")',
                'code_language' => 'python',
                'outputs' => [
                    ['variable' => 'result', 'value_selector' => ['code-1', 'result']],
                ],
            ],
        ];

        $node = CodeNode::fromArray($data);

        $this->assertSame('code-1', $node->getId());
        $this->assertSame('Test Code', $node->getTitle());
        $this->assertSame('Test Description', $node->getDescription());
        $this->assertSame('print("hello")', $node->getCode());
        $this->assertSame('python', $node->getCodeLanguage());
    }

    public function testGetNodeType(): void
    {
        $node = CodeNode::fromArray(['id' => 'test']);
        $this->assertSame('code', $node->getNodeType());
    }

    public function testSetCode(): void
    {
        $node = CodeNode::fromArray(['id' => 'test']);
        $node->setCode('new code');

        $data = $node->toArray();
        $this->assertIsArray($data['data']);
        $this->assertIsString($data['data']['code']);
        $this->assertSame('new code', $data['data']['code']);
    }

    public function testSetCodeLanguage(): void
    {
        $node = CodeNode::fromArray(['id' => 'test']);
        $node->setCodeLanguage('javascript');

        $data = $node->toArray();
        $this->assertIsArray($data['data']);
        $this->assertIsString($data['data']['code_language']);
        $this->assertSame('javascript', $data['data']['code_language']);
    }

    public function testSetOutputs(): void
    {
        $node = CodeNode::fromArray(['id' => 'test']);
        $outputs = [
            'output1' => ['type' => 'string'],
            'output2' => ['type' => 'number', 'children' => ['nested' => 'value']],
        ];
        $node->setOutputs($outputs);

        $data = $node->toArray();
        $this->assertIsArray($data['data']);
        $this->assertIsArray($data['data']['outputs']);
        $this->assertSame($outputs, $data['data']['outputs']);
    }

    public function testAddOutput(): void
    {
        $node = CodeNode::fromArray(['id' => 'test']);
        $node->addOutput('result', 'string', ['value_selector' => ['test', 'result']]);

        $data = $node->toArray();
        $expected = [
            'result' => ['type' => 'string', 'children' => ['value_selector' => ['test', 'result']]],
        ];
        $this->assertIsArray($data['data']);
        $this->assertIsArray($data['data']['outputs']);
        $this->assertSame($expected, $data['data']['outputs']);
    }

    public function testAddVariable(): void
    {
        $node = CodeNode::fromArray(['id' => 'test']);
        $valueSelector = ['node_id' => 'start', 'variable' => 'input'];

        $result = $node->addVariable('user_input', $valueSelector);

        // 方法应该返回自身实现链式调用
        $this->assertSame($node, $result);

        // 验证变量已添加
        $variables = $node->getVariables();
        $this->assertCount(1, $variables);
        $this->assertEquals([
            'variable' => 'user_input',
            'value_selector' => $valueSelector,
        ], $variables[0]);

        // 测试toArray包含variables
        $data = $node->toArray();
        $this->assertIsArray($data['data']);
        $this->assertArrayHasKey('variables', $data['data']);
        $this->assertIsArray($data['data']['variables']);
        $this->assertEquals([
            ['variable' => 'user_input', 'value_selector' => $valueSelector],
        ], $data['data']['variables']);
    }
}
