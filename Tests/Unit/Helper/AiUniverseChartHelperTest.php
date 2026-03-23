<?php

declare(strict_types=1);

namespace NITSAN\NsAiUniverse\Tests\Unit\Helper;

use NITSAN\NsAiUniverse\Helper\AiUniverseChartHelper;
use PHPUnit\Framework\TestCase;

final class AiUniverseChartHelperTest extends TestCase
{
    public function testProcessOpenAiUsageDataAggregatesBucketsByModel(): void
    {
        $helper = new AiUniverseChartHelper();

        $apiData = [
            'data' => [
                [
                    'results' => [
                        [
                            'model' => 'gpt-4o',
                            'num_model_requests' => 2,
                            'input_tokens' => 100,
                            'output_tokens' => 50,
                        ],
                    ],
                ],
                [
                    'results' => [
                        [
                            'model' => 'gpt-4o',
                            'num_model_requests' => 1,
                            'input_tokens' => 20,
                            'output_tokens' => 10,
                        ],
                    ],
                ],
            ],
        ];

        $result = $helper->processOpenAiUsageData($apiData);

        self::assertSame(3, $result['totalRequests']);
        self::assertSame(180, $result['totalTokens']);
        self::assertSame(3, $result['numberOfRequestData']['gpt-4o']);
        self::assertSame(120, $result['contextTokenData']['gpt-4o']);
        self::assertSame(60, $result['generatedTokenData']['gpt-4o']);
        self::assertSame(180, $result['totalTokenData']['gpt-4o']);
    }

    public function testGetChartConfigReturnsValidJsonPayload(): void
    {
        $helper = new AiUniverseChartHelper();

        $json = $helper->getChartConfig(
            ['gpt-4o-2024-08-06' => 10, 'gpt-3.5-turbo' => 4],
            'bar',
            'Usage',
        );

        $decoded = json_decode($json, true);

        self::assertIsArray($decoded);
        self::assertSame('bar', $decoded['type']);
        self::assertSame('Usage', $decoded['data']['datasets'][0]['label']);
        self::assertNotEmpty($decoded['data']['labels']);
        self::assertCount(2, $decoded['data']['datasets'][0]['data']);
    }
}
