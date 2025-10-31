<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Parser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyDsl\Core\App;
use Tourze\DifyDsl\Exception\ParseException;
use Tourze\DifyDsl\Parser\DifyParser;

/**
 * @internal
 */
#[CoversClass(DifyParser::class)]
class DifyParserTest extends TestCase
{
    private DifyParser $parser;

    protected function setUp(): void
    {
        $this->parser = new DifyParser();
    }

    public function testParseFromArrayBasic(): void
    {
        $data = [
            'app' => [
                'name' => 'Test Workflow',
                'description' => 'A test workflow',
                'mode' => 'workflow',
                'icon' => '🔥',
                'icon_background' => '#FF0000',
            ],
            'kind' => 'app',
            'version' => '0.2.0',
            'workflow' => [
                'graph' => [
                    'nodes' => [],
                    'edges' => [],
                ],
            ],
        ];

        $app = $this->parser->parseFromArray($data);

        $this->assertInstanceOf(App::class, $app);
        $this->assertEquals('Test Workflow', $app->getName());
        $this->assertEquals('A test workflow', $app->getDescription());
        $this->assertEquals('workflow', $app->getMode());
        $this->assertEquals('🔥', $app->getIcon());
        $this->assertEquals('#FF0000', $app->getIconBackground());
    }

    public function testParseFromArrayWithComplexWorkflow(): void
    {
        $data = [
            'app' => [
                'name' => 'Complex Workflow',
                'mode' => 'chat',
            ],
            'kind' => 'app',
            'version' => '0.2.0',
            'workflow' => [
                'graph' => [
                    'nodes' => [
                        [
                            'id' => 'start',
                            'data' => [
                                'type' => 'start',
                                'title' => 'Start Node',
                            ],
                        ],
                        [
                            'id' => 'llm1',
                            'data' => [
                                'type' => 'llm',
                                'title' => 'LLM Node',
                            ],
                        ],
                        [
                            'id' => 'end',
                            'data' => [
                                'type' => 'end',
                                'title' => 'End Node',
                            ],
                        ],
                    ],
                    'edges' => [
                        [
                            'id' => 'start-llm1',
                            'source' => 'start',
                            'target' => 'llm1',
                        ],
                        [
                            'id' => 'llm1-end',
                            'source' => 'llm1',
                            'target' => 'end',
                        ],
                    ],
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
                    'file_upload' => ['enabled' => true],
                ],
            ],
        ];

        $app = $this->parser->parseFromArray($data);

        $this->assertEquals('Complex Workflow', $app->getName());
        $this->assertEquals('chat', $app->getMode());

        $workflow = $app->getWorkflow();
        $this->assertNotNull($workflow);

        // 验证图结构
        $graph = $workflow->getGraph();
        $this->assertCount(3, $graph->getNodes());
        $this->assertCount(2, $graph->getEdges());

        // 验证变量
        $this->assertCount(1, $workflow->getEnvironmentVariables());
        $this->assertCount(1, $workflow->getConversationVariables());

        $envVar = $workflow->getEnvironmentVariables()[0];
        $this->assertEquals('api_key', $envVar->getVariable());
        $this->assertTrue($envVar->isRequired());

        // 验证特性
        $features = $workflow->getFeatures();
        $this->assertIsBool($features['speech_to_text']);
        $this->assertTrue($features['speech_to_text']);
        $this->assertIsArray($features['file_upload']);
        $this->assertIsBool($features['file_upload']['enabled']);
        $this->assertTrue($features['file_upload']['enabled']);
    }

    public function testParseYamlString(): void
    {
        $yamlContent = <<<'YAML'
            app:
              name: YAML Test
              description: Testing YAML parsing
              mode: workflow
              icon: "⚡"
            kind: app
            version: "0.2.0"
            workflow:
              graph:
                nodes: []
                edges: []
            YAML;

        $app = $this->parser->parse($yamlContent);

        $this->assertInstanceOf(App::class, $app);
        $this->assertEquals('YAML Test', $app->getName());
        $this->assertEquals('Testing YAML parsing', $app->getDescription());
        $this->assertEquals('workflow', $app->getMode());
        $this->assertEquals('⚡', $app->getIcon());
    }

    public function testParseInvalidYaml(): void
    {
        $invalidYaml = <<<'YAML'
            app:
              name: Invalid
              mode: workflow
            kind: app
            version: "0.2.0"
            workflow:
              graph:
                nodes: [
                  invalid_structure
            YAML;

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Failed to parse YAML');

        $this->parser->parse($invalidYaml);
    }

    public function testValidateStructureMissingRequiredKey(): void
    {
        $data = [
            'app' => [
                'name' => 'Test',
                'mode' => 'workflow',
            ],
            'kind' => 'app',
            'version' => '0.2.0',
            // 缺少 workflow
        ];

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Missing required key: workflow');

        $this->parser->parseFromArray($data);
    }

    public function testValidateStructureMissingAppKey(): void
    {
        $data = [
            'app' => [
                'name' => 'Test',
                // 缺少 mode
            ],
            'kind' => 'app',
            'version' => '0.2.0',
            'workflow' => ['graph' => ['nodes' => [], 'edges' => []]],
        ];

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Missing required app key: mode');

        $this->parser->parseFromArray($data);
    }

    public function testValidateStructureInvalidKind(): void
    {
        $data = [
            'app' => [
                'name' => 'Test',
                'mode' => 'workflow',
            ],
            'kind' => 'invalid',
            'version' => '0.2.0',
            'workflow' => ['graph' => ['nodes' => [], 'edges' => []]],
        ];

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid kind: expected 'app', got 'invalid'");

        $this->parser->parseFromArray($data);
    }

    public function testValidateStructureUnsupportedVersion(): void
    {
        $data = [
            'app' => [
                'name' => 'Test',
                'mode' => 'workflow',
            ],
            'kind' => 'app',
            'version' => '1.0.0',
            'workflow' => ['graph' => ['nodes' => [], 'edges' => []]],
        ];

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unsupported version: 1.0.0');

        $this->parser->parseFromArray($data);
    }

    public function testValidateStructureUnsupportedMode(): void
    {
        $data = [
            'app' => [
                'name' => 'Test',
                'mode' => 'invalid_mode',
            ],
            'kind' => 'app',
            'version' => '0.2.0',
            'workflow' => ['graph' => ['nodes' => [], 'edges' => []]],
        ];

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unsupported mode: invalid_mode');

        $this->parser->parseFromArray($data);
    }

    public function testSupportedVersions(): void
    {
        $supportedVersions = ['0.1.5', '0.2.0', '0.3.0'];

        foreach ($supportedVersions as $version) {
            $data = [
                'app' => [
                    'name' => 'Version Test',
                    'mode' => 'workflow',
                ],
                'kind' => 'app',
                'version' => $version,
                'workflow' => ['graph' => ['nodes' => [], 'edges' => []]],
            ];

            $app = $this->parser->parseFromArray($data);
            $this->assertEquals($version, $app->getVersion());
        }
    }

    public function testSupportedModes(): void
    {
        $supportedModes = ['workflow', 'chat', 'advanced-chat', 'agent-chat'];

        foreach ($supportedModes as $mode) {
            $data = [
                'app' => [
                    'name' => 'Mode Test',
                    'mode' => $mode,
                ],
                'kind' => 'app',
                'version' => '0.2.0',
                'workflow' => ['graph' => ['nodes' => [], 'edges' => []]],
            ];

            $app = $this->parser->parseFromArray($data);
            $this->assertEquals($mode, $app->getMode());
        }
    }

    public function testParseFileNotFound(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('File not found: /nonexistent/file.yaml');

        $this->parser->parseFile('/nonexistent/file.yaml');
    }

    public function testParseNodeError(): void
    {
        $data = [
            'app' => [
                'name' => 'Node Error Test',
                'mode' => 'workflow',
            ],
            'kind' => 'app',
            'version' => '0.2.0',
            'workflow' => [
                'graph' => [
                    'nodes' => [
                        [
                            'id' => 'invalid_node',
                            'data' => [
                                'type' => 'unknown_type',
                                'title' => 'Invalid Node',
                            ],
                        ],
                    ],
                    'edges' => [],
                ],
            ],
        ];

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Failed to parse node invalid_node');

        $this->parser->parseFromArray($data);
    }

    public function testParseEdgeError(): void
    {
        $data = [
            'app' => [
                'name' => 'Edge Error Test',
                'mode' => 'workflow',
            ],
            'kind' => 'app',
            'version' => '0.2.0',
            'workflow' => [
                'graph' => [
                    'nodes' => [],
                    'edges' => [
                        // 创建一个可能导致错误的边结构
                        'invalid_edge_structure',
                    ],
                ],
            ],
        ];

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Failed to parse edge');

        $this->parser->parseFromArray($data);
    }

    public function testMinimalValidStructure(): void
    {
        $data = [
            'app' => [
                'name' => 'Minimal',
                'mode' => 'workflow',
            ],
            'kind' => 'app',
            'version' => '0.2.0',
            'workflow' => [
                'graph' => [
                    'nodes' => [],
                    'edges' => [],
                ],
            ],
        ];

        $app = $this->parser->parseFromArray($data);

        $this->assertInstanceOf(App::class, $app);
        $this->assertEquals('Minimal', $app->getName());
        $this->assertEquals('workflow', $app->getMode());
        $this->assertEquals('0.2.0', $app->getVersion());
        $this->assertEquals('app', $app->getKind());
    }

    public function testWorkflowWithoutGraph(): void
    {
        $data = [
            'app' => [
                'name' => 'No Graph Test',
                'mode' => 'workflow',
            ],
            'kind' => 'app',
            'version' => '0.2.0',
            'workflow' => [],
        ];

        $app = $this->parser->parseFromArray($data);

        $workflow = $app->getWorkflow();
        $this->assertNotNull($workflow);

        $graph = $workflow->getGraph();
        $this->assertEmpty($graph->getNodes());
        $this->assertEmpty($graph->getEdges());
    }

    public function testComplexYamlParsing(): void
    {
        $complexYaml = <<<'YAML'
            app:
              name: "Complex YAML Test"
              description: "Testing complex YAML structures"
              mode: "chat"
              icon: "🚀"
              icon_background: "#1E88E5"
            kind: app
            version: "0.2.0"
            workflow:
              graph:
                nodes:
                  - id: start
                    type: custom
                    position:
                      x: 0
                      y: 0
                    data:
                      type: start
                      title: "开始"
                      variables:
                        - variable: query
                          label: "用户查询"
                          type: text-input
                          required: true
                  - id: llm
                    type: custom
                    position:
                      x: 200
                      y: 100
                    data:
                      type: llm
                      title: "LLM处理"
                      model:
                        name: gpt-4
                        provider: openai
                      prompt_template:
                        - role: system
                          text: "你是一个有用的助手"
                        - role: user
                          text: "请处理: {{query}}"
                edges:
                  - id: start-llm
                    source: start
                    target: llm
                    type: custom
              environment_variables:
                - variable: openai_api_key
                  label: "OpenAI API Key"
                  type: text-input
                  required: true
                  description: "OpenAI API密钥"
              features:
                speech_to_text: true
                file_upload:
                  enabled: true
                  allowed_file_types:
                    - image
                    - document
                  number_limits: 5
            YAML;

        $app = $this->parser->parse($complexYaml);

        $this->assertEquals('Complex YAML Test', $app->getName());
        $this->assertEquals('Testing complex YAML structures', $app->getDescription());
        $this->assertEquals('chat', $app->getMode());
        $this->assertEquals('🚀', $app->getIcon());
        $this->assertEquals('#1E88E5', $app->getIconBackground());

        $workflow = $app->getWorkflow();
        $graph = $workflow->getGraph();

        $this->assertCount(2, $graph->getNodes());
        $this->assertCount(1, $graph->getEdges());

        // 验证节点
        $startNode = $graph->getNode('start');
        $this->assertNotNull($startNode);
        $this->assertEquals('start', $startNode->getNodeType());

        $llmNode = $graph->getNode('llm');
        $this->assertNotNull($llmNode);
        $this->assertEquals('llm', $llmNode->getNodeType());

        // 验证边
        $edge = $graph->getEdge('start-llm');
        $this->assertNotNull($edge);
        $this->assertEquals('start', $edge->getSource());
        $this->assertEquals('llm', $edge->getTarget());

        // 验证环境变量
        $envVars = $workflow->getEnvironmentVariables();
        $this->assertCount(1, $envVars);
        $this->assertEquals('openai_api_key', $envVars[0]->getVariable());

        // 验证特性
        $features = $workflow->getFeatures();
        $this->assertIsBool($features['speech_to_text']);
        $this->assertTrue($features['speech_to_text']);
        $this->assertIsArray($features['file_upload']);
        $this->assertIsBool($features['file_upload']['enabled']);
        $this->assertTrue($features['file_upload']['enabled']);
        $this->assertIsArray($features['file_upload']['allowed_file_types']);
        $this->assertEquals(['image', 'document'], $features['file_upload']['allowed_file_types']);
    }
}
