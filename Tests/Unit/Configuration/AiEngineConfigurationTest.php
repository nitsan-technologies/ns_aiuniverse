<?php

declare(strict_types=1);

namespace NITSAN\NsAiUniverse\Tests\Unit\Configuration;

use NITSAN\NsAiUniverse\Configuration\AiEngineConfiguration;
use PHPUnit\Framework\TestCase;

final class AiEngineConfigurationTest extends TestCase
{
    private function createConfigurationWithoutConstructor(): AiEngineConfiguration
    {
        $reflection = new \ReflectionClass(AiEngineConfiguration::class);
        /** @var AiEngineConfiguration $configuration */
        $configuration = $reflection->newInstanceWithoutConstructor();

        $property = $reflection->getProperty('textGenerationAIEngines');
        $property->setValue($configuration, [
            'openai' => ['gpt-5'],
            'claude' => ['claude-sonnet-4-6'],
            'gemini' => ['gemini-1.5-flash'],
            'mistral' => ['mistral-large-latest'],
        ]);

        return $configuration;
    }

    public function testGetAllEnginesReturnsMappedEngineKeys(): void
    {
        $configuration = $this->createConfigurationWithoutConstructor();

        $engines = $configuration->getAllEngines();

        self::assertSame(['openai', 'claude', 'gemini', 'mistral'], array_keys($engines));
        self::assertSame('openai', $engines['openai']);
        self::assertSame('claude', $engines['claude']);
    }

    public function testGetTextGenerateEnginesReturnsExpectedStructure(): void
    {
        $configuration = $this->createConfigurationWithoutConstructor();

        $result = $configuration->getTextGenerateEngines();

        self::assertArrayHasKey('mainModules', $result);
        self::assertArrayHasKey('allModules', $result);
        self::assertIsArray($result['mainModules']);
        self::assertIsArray($result['allModules']);
        self::assertSame(['openai', 'claude', 'gemini', 'mistral'], array_keys($result['allModules']));
    }

    // getTextGenerationAIEngines() currently reads TYPO3 extension config before
    // checking ignoreConfig, so it requires a TYPO3 runtime context.
}
