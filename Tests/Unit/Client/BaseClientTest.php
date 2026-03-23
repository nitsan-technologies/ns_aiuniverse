<?php

declare(strict_types=1);

namespace NITSAN\NsAiUniverse\Tests\Unit\Client;

use NITSAN\NsAiUniverse\Client\BaseClient;
use PHPUnit\Framework\TestCase;

final class BaseClientTest extends TestCase
{
    private function createClientWithoutConstructor(): BaseClient
    {
        $reflection = new \ReflectionClass(BaseClient::class);
        /** @var BaseClient $client */
        $client = $reflection->newInstanceWithoutConstructor();
        return $client;
    }

    public function testBuildMessageHistoryForOpenAi(): void
    {
        $client = $this->createClientWithoutConstructor();

        $messages = $client->buildMessageHistory('openai', 'system prompt', 'hello');

        self::assertSame('system', $messages[0]['role']);
        self::assertSame('system prompt', $messages[0]['content']);
        self::assertSame('user', $messages[1]['role']);
        self::assertSame('hello', $messages[1]['content']);
    }

    public function testGetStreamChunkTextForGeminiAndClaude(): void
    {
        $client = $this->createClientWithoutConstructor();

        $geminiText = $client->getStreamChunkText('gemini', [
            'candidates' => [
                ['content' => ['parts' => [['text' => 'gemini delta']]]],
            ],
        ]);
        $claudeText = $client->getStreamChunkText('claude', [
            'type' => 'content_block_delta',
            'delta' => ['type' => 'text_delta', 'text' => 'claude delta'],
        ]);

        self::assertSame('gemini delta', $geminiText);
        self::assertSame('claude delta', $claudeText);
    }

    public function testParseEmbeddingResponseForOpenAiAndGemini(): void
    {
        $client = $this->createClientWithoutConstructor();

        $openAiResult = $client->parseEmbeddingResponse('openai', [
            'data' => [
                ['embedding' => [0.1, 0.2, 0.3]],
            ],
            'usage' => [
                'total_tokens' => 12,
            ],
        ]);
        $geminiResult = $client->parseEmbeddingResponse('gemini', [
            'embedding' => [
                'values' => [0.9, 0.8],
            ],
        ]);

        self::assertIsArray($openAiResult);
        self::assertSame([0.1, 0.2, 0.3], $openAiResult['embedding']);
        self::assertSame(12, $openAiResult['token_used']);
        self::assertSame([0.9, 0.8], $geminiResult);
    }
}
