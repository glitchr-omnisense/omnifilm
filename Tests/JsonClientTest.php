<?php

namespace Omnifilm\Tests;

use Omnifilm\Exception\ProviderException;
use Omnifilm\Exception\UnreachableException;
use Omnifilm\Http\Data;
use Omnifilm\Http\JsonClient;
use Omnifilm\Testing\RecordedHttpClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;

final class JsonClientTest extends TestCase
{
    public function testDataReadsWhateverShapeItIsGiven(): void
    {
        $data = new Data(['title' => ' Stalker ', 'year' => '1979', 'score' => 8.1, 'adult' => false, 'empty' => '', 'release' => ['date' => '1979-05-25T00:00:00Z'], 'cast' => [['name' => 'A'], 'x', ['name' => 'B'], ['name' => 'A']], 'tags' => ['a', 1, null, ['b']]]);

        self::assertSame('Stalker', $data->string('title'));
        self::assertSame(1979, $data->int('year'));
        self::assertSame(8.1, $data->float('score'));
        self::assertFalse($data->bool('adult'));
        self::assertNull($data->string('empty'));
        self::assertNull($data->int('title'));
        $date = $data->date('release.date');
        self::assertNotNull($date);
        self::assertSame('1979-05-25 00:00:00 UTC', $date->format('Y-m-d H:i:s T'));
        self::assertNull($data->date('title'));
        self::assertSame(['A', 'B'], $data->strings('cast', 'name'));
        self::assertSame(['a', '1'], $data->strings('tags'));
        self::assertCount(3, $data->list('cast'));
        self::assertSame([], $data->list('title'));
        self::assertNull($data->data('nothing')->string('x'));
        self::assertSame('2011-04-18', Data::of(['d' => '18 Apr, 2011'])->date('d', 'j M, Y')?->format('Y-m-d'));
        self::assertCount(2, Data::of([['a' => 1], 'x', ['b' => 2]])->items());
    }

    public function testAnswersErrorsAndUnreachableProviders(): void
    {
        $http = RecordedHttpClient::routes([
            '~/ok\?api_key=not-a-real-key&language=fr$~' => [200, '{"id": 1}'],
            '~/text~' => [200, 'plain text'],
            '~/denied~' => [401, '{"status_message": "Invalid API key: You must be granted a valid key."}'],
        ]);
        $json = new JsonClient($http, 'Example', ['Accept' => 'application/json']);

        self::assertSame(1, $json->get('https://api.example.org/ok', ['api_key' => 'not-a-real-key', 'language' => 'fr', 'region' => null])?->int('id'));
        self::assertSame('application/json', $http->header('Accept'));
        self::assertNull($json->get('https://api.example.org/missing'));
        try {
            $json->get('https://api.example.org/denied', ['api_key' => 'not-a-real-key']);
            self::fail('401');
        } catch (ProviderException $e) {
            self::assertSame('Example answered 401 for GET /denied. {"status_message": "Invalid API key: You must be granted a valid key."}', $e->getMessage());
            self::assertStringNotContainsString('not-a-real-key', $e->getMessage());
        }
        try {
            $json->get('https://api.example.org/text');
            self::fail('Not JSON');
        } catch (ProviderException $e) {
            self::assertStringContainsString('not JSON', $e->getMessage());
        }

        $this->expectException(UnreachableException::class);
        (new JsonClient(new MockHttpClient(static fn () => throw new TransportException('Could not resolve host')), 'Example'))->get('https://api.example.org/ok');
    }
}
