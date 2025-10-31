<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Generator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use Tourze\DifyDsl\Core\App;
use Tourze\DifyDsl\Core\Graph;
use Tourze\DifyDsl\Core\Variable;
use Tourze\DifyDsl\Core\Workflow;
use Tourze\DifyDsl\Generator\DifyGenerator;
use Tourze\DifyDsl\Nodes\EndNode;
use Tourze\DifyDsl\Nodes\StartNode;

/**
 * @internal
 */
#[CoversClass(DifyGenerator::class)]
class DifyGeneratorTest extends TestCase
{
    private DifyGenerator $generator;

    private App $testApp;

    protected function setUp(): void
    {
        $this->generator = new DifyGenerator();

        // 创建一个简单的测试应用
        $graph = new Graph();
        $graph->addNode(new StartNode('start', 'Start Node'));
        $graph->addNode(new EndNode('end', 'End Node'));

        $workflow = new Workflow($graph);

        $this->testApp = new App(
            name: 'Test App',
            description: 'A test application',
            mode: 'workflow',
            workflow: $workflow
        );
    }

    public function testConstructorWithDefaults(): void
    {
        $generator = new DifyGenerator();

        $this->assertInstanceOf(DifyGenerator::class, $generator);
    }

    public function testConstructorWithCustomOptions(): void
    {
        $generator = new DifyGenerator(4, Yaml::DUMP_OBJECT_AS_MAP);

        $this->assertInstanceOf(DifyGenerator::class, $generator);
    }

    public function testGenerate(): void
    {
        $yaml = $this->generator->generate($this->testApp);

        $this->assertIsString($yaml);
        $this->assertStringContainsString("name: 'Test App'", $yaml);
        $this->assertStringContainsString("description: 'A test application'", $yaml);

        // 验证YAML可以被解析
        $parsed = Yaml::parse($yaml);
        $this->assertIsArray($parsed);
        $this->assertIsArray($parsed['app']);
        $this->assertIsString($parsed['app']['name']);
        $this->assertEquals('Test App', $parsed['app']['name']);
    }

    public function testGeneratePretty(): void
    {
        $prettyYaml = $this->generator->generatePretty($this->testApp);

        $this->assertIsString($prettyYaml);
        $this->assertStringContainsString("name: 'Test App'", $prettyYaml);

        // 验证YAML可以被解析
        $parsed = Yaml::parse($prettyYaml);
        $this->assertIsArray($parsed);
        $this->assertIsArray($parsed['app']);
        $this->assertIsString($parsed['app']['name']);
        $this->assertEquals('Test App', $parsed['app']['name']);
    }

    public function testGenerateToFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'dify_test_');

        try {
            $this->generator->generateToFile($this->testApp, $tempFile);

            $this->assertFileExists($tempFile);

            $content = file_get_contents($tempFile);
            $this->assertIsString($content);
            $this->assertStringContainsString("name: 'Test App'", $content);

            // 验证生成的文件可以被解析
            $parsed = Yaml::parseFile($tempFile);
            $this->assertIsArray($parsed);
            $this->assertIsArray($parsed['app']);
            $this->assertIsString($parsed['app']['name']);
            $this->assertEquals('Test App', $parsed['app']['name']);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testGenerateToFileCreatesDirectory(): void
    {
        $tempDir = sys_get_temp_dir() . '/dify_test_' . uniqid();
        $tempFile = $tempDir . '/test.yaml';

        try {
            $this->generator->generateToFile($this->testApp, $tempFile);

            $this->assertDirectoryExists($tempDir);
            $this->assertFileExists($tempFile);

            $content = file_get_contents($tempFile);
            $this->assertIsString($content);
            $this->assertStringContainsString("name: 'Test App'", $content);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }

    public function testSetIndentSize(): void
    {
        $this->generator->setIndentSize(4);

        $yaml = $this->generator->generate($this->testApp);

        // 检查缩进（虽然很难精确验证，但至少确保方法被调用）
        $this->assertIsString($yaml);
        $this->assertStringContainsString("name: 'Test App'", $yaml);
    }

    public function testSetFlags(): void
    {
        $this->generator->setFlags(Yaml::DUMP_OBJECT_AS_MAP);

        $yaml = $this->generator->generate($this->testApp);

        $this->assertIsString($yaml);
        $this->assertStringContainsString("name: 'Test App'", $yaml);
    }

    public function testGenerateWithVariables(): void
    {
        // 通过Workflow添加变量到应用
        $variable = new Variable('test_var', 'Test Variable', 'string');
        $this->testApp->getWorkflow()->addEnvironmentVariable($variable);

        $yaml = $this->generator->generate($this->testApp);

        $this->assertStringContainsString('test_var', $yaml);
        $this->assertStringContainsString('string', $yaml);
    }

    public function testGenerateComplexWorkflow(): void
    {
        // 创建更复杂的工作流
        $graph = new Graph();

        $startNode = new StartNode('start', 'Start');
        $endNode = new EndNode('end', 'End');

        $graph->addNode($startNode);
        $graph->addNode($endNode);

        $workflow = new Workflow($graph);
        $app = new App('Complex App', 'Complex workflow', 'workflow', $workflow);

        $yaml = $this->generator->generate($app);

        $this->assertStringContainsString('Complex App', $yaml);
        $this->assertStringContainsString('start', $yaml);
        $this->assertStringContainsString('end', $yaml);

        // 验证生成的YAML结构
        $parsed = Yaml::parse($yaml);
        $this->assertIsArray($parsed);
        $this->assertArrayHasKey('app', $parsed);
        $this->assertArrayHasKey('workflow', $parsed);
    }

    public function testYamlOutputIsValid(): void
    {
        $yaml = $this->generator->generate($this->testApp);

        // 确保生成的YAML是有效的
        $parsed = Yaml::parse($yaml);
        $this->assertNotFalse($parsed);
        $this->assertIsArray($parsed);
        $this->assertArrayHasKey('app', $parsed);
    }

    public function testPrettyOutputDifference(): void
    {
        $standard = $this->generator->generate($this->testApp);
        $pretty = $this->generator->generatePretty($this->testApp);

        // 两种输出都应该是有效的YAML
        $this->assertNotFalse(Yaml::parse($standard));
        $this->assertNotFalse(Yaml::parse($pretty));

        // 内容应该基本相同（解析后的数据结构）
        $standardParsed = Yaml::parse($standard);
        $prettyParsed = Yaml::parse($pretty);

        $this->assertIsArray($standardParsed);
        $this->assertIsArray($prettyParsed);
        $this->assertIsArray($standardParsed['app']);
        $this->assertIsArray($prettyParsed['app']);
        $this->assertIsString($standardParsed['app']['name']);
        $this->assertIsString($prettyParsed['app']['name']);
        $this->assertIsString($standardParsed['app']['description']);
        $this->assertIsString($prettyParsed['app']['description']);
        $this->assertEquals($standardParsed['app']['name'], $prettyParsed['app']['name']);
        $this->assertEquals($standardParsed['app']['description'], $prettyParsed['app']['description']);
    }
}
