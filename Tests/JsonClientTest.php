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

    /**
     * Not reached: the message names the method and the path, and the HTTP client's reason
     * without its address - Symfony's transport messages quote the URL, query and key included.
     */
    public function testAnUnreachableProviderIsNamedByItsPathNeverItsQuery(): void
    {
        $http = new MockHttpClient(static function (string $method, string $url): never {
            throw new TransportException(\sprintf('Could not resolve host: www.omdbapi.com for "%s".', $url));
        });

        try {
            (new JsonClient($http, 'OMDb'))->get('https://www.omdbapi.com/', ['apikey' => 'secret-omdb-key', 'i' => 'tt0133093']);
            self::fail('no exception');
        } catch (UnreachableException $e) {
            self::assertSame('OMDb could not be reached for GET /: Could not resolve host: www.omdbapi.com.', $e->getMessage());
            self::assertStringNotContainsString('secret-omdb-key', $e->getMessage());
        }
    }

    /**
     * Nothing of the key anywhere in what a log of the whole chain writes: the messages, the
     * previous exception's (a copy of the HTTP client's, its class and code kept, the key's
     * parameter masked), and the traces' arguments, which PHP keeps unless
     * zend.exception_ignore_args is on.
     */
    public function testNoPartOfAnUnreachableChainHoldsTheKey(): void
    {
        $ignoreArgs = ini_set('zend.exception_ignore_args', '0');
        $http = new MockHttpClient(static function (string $method, string $url): never {
            throw new TransportException(\sprintf('Could not resolve host: api.example.org for "%s".', $url), 6);
        });

        try {
            (new JsonClient($http, 'Example', ['Authorization' => 'Bearer bearer-secret-token']))->get('https://api.example.org/search', ['api_key' => 'secret-film-key', 'query' => 'Stalker']);
            self::fail('no exception');
        } catch (UnreachableException $e) {
            $previous = $e->getPrevious();
            self::assertInstanceOf(TransportException::class, $previous);
            self::assertSame(6, $previous->getCode());
            self::assertStringContainsString('api_key=***', $previous->getMessage());
            self::assertNotEmpty($e->getTrace());
            for ($one = $e; null !== $one; $one = $one->getPrevious()) {
                foreach (['secret-film-key', 'bearer-secret-token'] as $secret) {
                    self::assertStringNotContainsString($secret, $one->getMessage());
                    self::assertStringNotContainsString($secret, print_r($one->getTrace(), true));
                    self::assertStringNotContainsString($secret, $one->getTraceAsString());
                }
            }
            self::assertStringNotContainsString('secret-film-key', (string) $e);
        } finally {
            ini_set('zend.exception_ignore_args', false !== $ignoreArgs ? $ignoreArgs : '1');
        }
    }

    /**
     * An error answered - the key refused, nothing there, a server error, a body that is not JSON:
     * the exception thrown where the request's options stood keeps no argument in its trace, so a
     * log of it (zend.exception_ignore_args off) holds no key.
     */
    public function testAnErrorAnswerKeepsNoKeyInItsTrace(): void
    {
        $ignoreArgs = ini_set('zend.exception_ignore_args', '0');
        try {
            foreach ([[401, '{"message": "no"}'], [404, '{}'], [500, '{"message": "down"}'], [200, 'plain text']] as [$status, $body]) {
                $http = new \Symfony\Component\HttpClient\MockHttpClient(new \Symfony\Component\HttpClient\Response\MockResponse($body, ['http_code' => $status]));
                try {
                    (new JsonClient($http, 'Example', ['Authorization' => 'Bearer error-token-1234567']))->get('https://api.example.org/search', ['api_key' => 'error-key-1234567']);
                    if (404 === $status) {
                        continue;
                    }
                    self::fail("no exception on $status");
                } catch (\Omnifilm\Exception\OmnifilmException $e) {
                    self::assertNotEmpty($e->getTrace());
                    foreach (['error-key-1234567', 'error-token-1234567'] as $secret) {
                        self::assertFalse(str_contains($e->getMessage(), $secret), $e->getMessage());
                        self::assertFalse(str_contains(print_r($e->getTrace(), true), $secret), "$status: the trace holds $secret");
                    }
                }
            }
        } finally {
            ini_set('zend.exception_ignore_args', false !== $ignoreArgs ? $ignoreArgs : '1');
        }
    }
}
