<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace HyperfTest\JsonRpc;

use Hyperf\JsonRpc\DataFormatter;
use Hyperf\JsonRpc\NormalizeDataFormatter;
use Hyperf\RpcClient\Exception\RequestException;
use Hyperf\Utils\Serializer\SerializerFactory;
use Hyperf\Utils\Serializer\SymfonyNormalizer;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class DataFormatterTest extends TestCase
{
    protected function tearDown()
    {
        Mockery::close();
    }

    public function testFormatErrorResponse()
    {
        $formatter = new DataFormatter();
        $data = $formatter->formatErrorResponse([$id = uniqid(), 500, 'Error', new \RuntimeException('test case', 1000)]);

        $this->assertEquals([
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => 500,
                'message' => 'Error',
                'data' => [
                    'class' => 'RuntimeException',
                    'code' => 1000,
                    'message' => 'test case',
                ],
            ],
        ], $data);

        $exception = new RequestException('', 0, $data['error']['data']);
        $this->assertSame(1000, $exception->getThrowableCode());
        $this->assertSame('test case', $exception->getThrowableMessage());
    }

    public function testNormalizeFormatErrorResponse()
    {
        $normalizer = new SymfonyNormalizer((new SerializerFactory())());

        $formatter = new NormalizeDataFormatter($normalizer);
        $data = $formatter->formatErrorResponse([$id = uniqid(), 500, 'Error', new \RuntimeException('test case', 1000)]);

        $this->assertArrayHasKey('line', $data['error']['data']['attributes']);
        $this->assertArrayHasKey('file', $data['error']['data']['attributes']);

        $exception = new RequestException('', 0, $data['error']['data']);
        $this->assertSame(1000, $exception->getThrowableCode());
        $this->assertSame('test case', $exception->getThrowableMessage());

        unset($data['error']['data']['attributes']['line'], $data['error']['data']['attributes']['file']);

        $this->assertEquals([
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => 500,
                'message' => 'Error',
                'data' => [
                    'class' => 'RuntimeException',
                    'attributes' => [
                        'code' => 1000,
                        'message' => 'test case',
                    ],
                ],
            ],
        ], $data);
    }
}
