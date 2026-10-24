<?php

namespace Omnifilm\Http;

use Omnifilm\Exception\ProviderException;
use Omnifilm\Exception\UnreachableException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * A provider's JSON calls over the application's HTTP client: the
 * provider's headers on every request, a 404 read as "not known" (null),
 * any other error status as a ProviderException naming the path only (never
 * the query, where a key may sit), a network failure as an
 * UnreachableException.
 */
final class JsonClient
{
    /** @param array<string, string> $headers sent with every request */
    public function __construct(private readonly HttpClientInterface $http, private readonly string $provider, private readonly array $headers = [])
    {
    }

    /**
     * @param array<string, string|int|float|bool|null> $query
     * @param array<string, string>                     $headers
     *
     * @return ?Data null when the provider answers 404
     */
    public function get(string $url, array $query = [], array $headers = []): ?Data
    {
        return $this->request('GET', $url, ['query' => array_filter($query, static fn ($value) => null !== $value)], $headers);
    }

    /**
     * @param array<string, mixed>  $options json, body or query, as Symfony's HttpClient takes them
     * @param array<string, string> $headers
     *
     * @return ?Data null when the provider answers 404
     */
    public function request(string $method, string $url, array $options = [], array $headers = []): ?Data
    {
        $what = $method.' '.(parse_url($url, \PHP_URL_PATH) ?: '/');
        try {
            $response = $this->http->request($method, $url, ['headers' => $headers + $this->headers] + $options);
            $status = $response->getStatusCode();
            if (404 === $status) {
                return null;
            }
            $body = $response->getContent(false);
        } catch (ExceptionInterface $e) {
            throw new UnreachableException(\sprintf('%s could not be reached for %s: %s', $this->provider, $what, $e->getMessage()), 0, $e);
        }
        if ($status >= 400) {
            throw ProviderException::answered($this->provider, $what, $status, $body);
        }
        $data = json_decode($body, true);

        return \is_array($data) ? new Data($data) : throw ProviderException::answered($this->provider, $what, $status, 'The body is not JSON.');
    }
}
