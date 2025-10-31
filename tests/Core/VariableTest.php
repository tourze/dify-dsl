<?php

declare(strict_types=1);

namespace Tourze\DifyDsl\Tests\Core;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DifyDsl\Core\Variable;

/**
 * @internal
 */
#[CoversClass(Variable::class)]
class VariableTest extends TestCase
{
    public function testCreateVariable(): void
    {
        $variable = new Variable(
            variable: 'user_input',
            label: 'User Input',
            type: 'text-input',
            required: true,
            description: 'User input field',
            defaultValue: 'default text',
            maxLength: 100
        );

        $this->assertEquals('user_input', $variable->getVariable());
        $this->assertEquals('User Input', $variable->getLabel());
        $this->assertEquals('text-input', $variable->getType());
        $this->assertTrue($variable->isRequired());
        $this->assertEquals('User input field', $variable->getDescription());
        $this->assertEquals('default text', $variable->getDefaultValue());
        $this->assertEquals(100, $variable->getMaxLength());
        $this->assertEquals([], $variable->getOptions());
        $this->assertEquals([], $variable->getAllowedFileExtensions());
        $this->assertEquals([], $variable->getAllowedFileTypes());
        $this->assertEquals([], $variable->getAllowedFileUploadMethods());
    }

    public function testCreateVariableWithOptionalArrays(): void
    {
        $variable = new Variable(
            variable: 'file_input',
            label: 'File Input',
            type: 'file',
            options: ['key1' => 'option1', 'key2' => 'option2'],
            allowedFileExtensions: ['.pdf', '.doc'],
            allowedFileTypes: ['application/pdf'],
            allowedFileUploadMethods: ['upload', 'url']
        );

        $this->assertEquals(['key1' => 'option1', 'key2' => 'option2'], $variable->getOptions());
        $this->assertEquals(['.pdf', '.doc'], $variable->getAllowedFileExtensions());
        $this->assertEquals(['application/pdf'], $variable->getAllowedFileTypes());
        $this->assertEquals(['upload', 'url'], $variable->getAllowedFileUploadMethods());
    }

    public function testFromArray(): void
    {
        $data = [
            'variable' => 'test_var',
            'label' => 'Test Variable',
            'type' => 'select',
            'required' => true,
            'description' => 'A test variable',
            'default' => 'test_default',
            'max_length' => 50,
            'options' => ['opt1', 'opt2'],
            'allowed_file_extensions' => ['.txt'],
            'allowed_file_types' => ['text/plain'],
            'allowed_file_upload_methods' => ['upload'],
        ];

        $variable = Variable::fromArray($data);

        $this->assertEquals('test_var', $variable->getVariable());
        $this->assertEquals('Test Variable', $variable->getLabel());
        $this->assertEquals('select', $variable->getType());
        $this->assertTrue($variable->isRequired());
        $this->assertEquals('A test variable', $variable->getDescription());
        $this->assertEquals('test_default', $variable->getDefaultValue());
        $this->assertEquals(50, $variable->getMaxLength());
        $this->assertEquals(['opt1', 'opt2'], $variable->getOptions());
        $this->assertEquals(['.txt'], $variable->getAllowedFileExtensions());
        $this->assertEquals(['text/plain'], $variable->getAllowedFileTypes());
        $this->assertEquals(['upload'], $variable->getAllowedFileUploadMethods());
    }

    public function testFromArrayWithDefaults(): void
    {
        $data = [];

        $variable = Variable::fromArray($data);

        $this->assertEquals('', $variable->getVariable());
        $this->assertEquals('', $variable->getLabel());
        $this->assertEquals('text-input', $variable->getType());
        $this->assertFalse($variable->isRequired());
        $this->assertNull($variable->getDescription());
        $this->assertNull($variable->getDefaultValue());
        $this->assertNull($variable->getMaxLength());
        $this->assertEquals([], $variable->getOptions());
    }

    public function testToArray(): void
    {
        $variable = new Variable(
            variable: 'test_var',
            label: 'Test Variable',
            type: 'text-input',
            required: true,
            description: 'Test description',
            defaultValue: 'default',
            maxLength: 100,
            options: ['key1' => 'opt1', 'key2' => 'opt2'],
            allowedFileExtensions: ['.txt'],
            allowedFileTypes: ['text/plain'],
            allowedFileUploadMethods: ['upload']
        );

        $array = $variable->toArray();

        $this->assertEquals([
            'variable' => 'test_var',
            'label' => 'Test Variable',
            'type' => 'text-input',
            'required' => true,
            'description' => 'Test description',
            'default' => 'default',
            'max_length' => 100,
            'options' => ['key1' => 'opt1', 'key2' => 'opt2'],
            'allowed_file_extensions' => ['.txt'],
            'allowed_file_types' => ['text/plain'],
            'allowed_file_upload_methods' => ['upload'],
        ], $array);
    }

    public function testToArrayWithNullValues(): void
    {
        $variable = new Variable(
            variable: 'simple_var',
            label: 'Simple Variable',
            type: 'text-input'
        );

        $array = $variable->toArray();

        $this->assertEquals([
            'variable' => 'simple_var',
            'label' => 'Simple Variable',
            'type' => 'text-input',
            'required' => false,
        ], $array);

        // 确保 null 和空数组不会出现在输出中
        $this->assertArrayNotHasKey('description', $array);
        $this->assertArrayNotHasKey('default', $array);
        $this->assertArrayNotHasKey('max_length', $array);
        $this->assertArrayNotHasKey('options', $array);
        $this->assertArrayNotHasKey('allowed_file_extensions', $array);
        $this->assertArrayNotHasKey('allowed_file_types', $array);
        $this->assertArrayNotHasKey('allowed_file_upload_methods', $array);
    }

    public function testTypeValidation(): void
    {
        $variable = new Variable(
            variable: 'num_var',
            label: 'Number Variable',
            type: 'number',
            defaultValue: 42
        );

        $this->assertEquals('number', $variable->getType());
        $this->assertEquals(42, $variable->getDefaultValue());
    }

    public function testFileVariableConfiguration(): void
    {
        $variable = new Variable(
            variable: 'upload_field',
            label: 'Upload Field',
            type: 'file',
            allowedFileExtensions: ['.pdf', '.doc', '.docx'],
            allowedFileTypes: ['application/pdf', 'application/msword'],
            allowedFileUploadMethods: ['upload', 'remote_url']
        );

        $this->assertEquals('file', $variable->getType());
        $this->assertEquals(['.pdf', '.doc', '.docx'], $variable->getAllowedFileExtensions());
        $this->assertEquals(['application/pdf', 'application/msword'], $variable->getAllowedFileTypes());
        $this->assertEquals(['upload', 'remote_url'], $variable->getAllowedFileUploadMethods());
    }
}
