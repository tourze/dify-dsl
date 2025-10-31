<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Core;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyDsl\Core\App;
use Tourze\DifyDsl\Core\Workflow;

/**
 * @internal
 */
#[CoversClass(App::class)]
class AppTest extends TestCase
{
    public function testCreateApp(): void
    {
        $app = App::create('Test App', 'workflow');

        $this->assertEquals('Test App', $app->getName());
        $this->assertEquals('workflow', $app->getMode());
        $this->assertEquals('app', $app->getKind());
        $this->assertEquals('0.2.0', $app->getVersion());
        $this->assertInstanceOf(Workflow::class, $app->getWorkflow());
    }

    public function testFromArray(): void
    {
        $data = [
            'app' => [
                'name' => 'Test Workflow',
                'description' => 'A test workflow',
                'mode' => 'chat',
                'icon' => '🔥',
                'icon_background' => '#FF0000',
                'use_icon_as_answer_icon' => true,
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

        $app = App::fromArray($data);

        $this->assertEquals('Test Workflow', $app->getName());
        $this->assertEquals('A test workflow', $app->getDescription());
        $this->assertEquals('chat', $app->getMode());
        $this->assertEquals('🔥', $app->getIcon());
        $this->assertEquals('#FF0000', $app->getIconBackground());
        $this->assertTrue($app->isUseIconAsAnswerIcon());
    }

    public function testToArray(): void
    {
        $app = App::create('Test App', 'workflow');
        $app->setDescription('Test description');
        $app->setIcon('⚡');
        $app->setIconBackground('#BLUE');

        $array = $app->toArray();

        $this->assertArrayHasKey('app', $array);
        $this->assertArrayHasKey('kind', $array);
        $this->assertArrayHasKey('version', $array);
        $this->assertArrayHasKey('workflow', $array);

        $appData = $array['app'] ?? [];
        $this->assertIsArray($appData);
        $this->assertEquals('Test App', $appData['name'] ?? '');
        $this->assertEquals('Test description', $appData['description'] ?? '');
        $this->assertEquals('workflow', $appData['mode'] ?? '');
        $this->assertEquals('⚡', $appData['icon'] ?? '');
        $this->assertEquals('#BLUE', $appData['icon_background'] ?? '');
    }

    public function testSetters(): void
    {
        $app = App::create('Original', 'workflow');

        // 修复：setter 方法现在返回 void，不支持链式调用
        $app->setName('Updated Name');
        $app->setDescription('Updated Description');
        $app->setMode('chat');
        $app->setIcon('🎯');
        $app->setIconBackground('#GREEN');
        $app->setUseIconAsAnswerIcon(true);

        $this->assertEquals('Updated Name', $app->getName());
        $this->assertEquals('Updated Description', $app->getDescription());
        $this->assertEquals('chat', $app->getMode());
        $this->assertEquals('🎯', $app->getIcon());
        $this->assertEquals('#GREEN', $app->getIconBackground());
        $this->assertTrue($app->isUseIconAsAnswerIcon());
    }

    public function testAddDependency(): void
    {
        $app = App::create('Test App', 'workflow');

        $dependency = [
            'name' => 'test-package',
            'version' => '1.0.0',
            'type' => 'library',
        ];

        $result = $app->addDependency('test-lib', $dependency);

        // 方法应该返回自身实现链式调用
        $this->assertSame($app, $result);

        // 验证依赖已添加
        $dependencies = $app->getDependencies();
        $this->assertArrayHasKey('test-lib', $dependencies);
        $this->assertEquals($dependency, $dependencies['test-lib']);

        // 测试toArray包含dependencies
        $array = $app->toArray();
        $this->assertArrayHasKey('dependencies', $array);
        $this->assertIsArray($array['dependencies']);
        $this->assertArrayHasKey('test-lib', $array['dependencies']);
    }
}
