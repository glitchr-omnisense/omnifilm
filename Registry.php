<?php

namespace Omnifilm;

use Omnifilm\Exception\InvalidConfigException;
use Omnifilm\Exception\NotSupportedException;
use Omnifilm\Model\Work;
use Omnifilm\Model\WorkRef;

/**
 * The application's providers by name, each built once from its factory and
 * options:
 *
 *   $registry = new Registry([new TmdbFactory($http), new WikidataFactory($http)], [
 *       'films' => ['factory' => 'tmdb', 'options' => ['token' => '...']],
 *       'open' => ['factory' => 'wikidata', 'options' => ['user_agent' => 'MySite/1.0 (https://example.org; films@example.org)']],
 *   ]);
 *
 *   $registry->search('films')->search(new Query('Stalker', WorkType::Movie));
 *   $registry->work(WorkRef::imdb('tt0079944'));   // the first provider that finds it
 */
final class Registry
{
    /** @var array<string, ProviderFactoryInterface> */
    private array $factories = [];

    /** @var array<string, ProviderInterface> */
    private array $providers = [];

    /**
     * @param iterable<ProviderFactoryInterface>                                     $factories
     * @param array<string, array{factory: string, options?: array<string, mixed>}> $config
     */
    public function __construct(iterable $factories, private readonly array $config)
    {
        foreach ($factories as $factory) {
            $this->factories[$factory->getName()] = $factory;
        }
    }

    public function get(string $name): ProviderInterface
    {
        return $this->providers[$name] ??= $this->factory($name)->create($this->config[$name]['options'] ?? []);
    }

    public function has(string $name): bool
    {
        return isset($this->config[$name]);
    }

    public function search(string $name): SearchInterface
    {
        $provider = $this->get($name);

        return $provider instanceof SearchInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'search');
    }

    public function lookup(string $name): LookupInterface
    {
        $provider = $this->get($name);

        return $provider instanceof LookupInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'look a work up');
    }

    public function episodes(string $name): EpisodesInterface
    {
        $provider = $this->get($name);

        return $provider instanceof EpisodesInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'list episodes');
    }

    public function availability(string $name): AvailabilityInterface
    {
        $provider = $this->get($name);

        return $provider instanceof AvailabilityInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'say where to watch');
    }

    /**
     * The work from the first provider, in the configuration's order (or the
     * order named), that finds it by this register: an IMDb number read from
     * Wikidata looked up at TMDB, a TMDB number at Trakt.
     */
    public function work(WorkRef $ref, ?string $language = null, string ...$names): ?Work
    {
        foreach ($names ?: $this->names() as $name) {
            $provider = $this->get($name);
            if ($provider instanceof LookupInterface && $provider->capabilities()->resolves($ref) && null !== $work = $provider->work($ref, $language)) {
                return $work;
            }
        }

        return null;
    }

    /** @return array<string, mixed> the options a provider is configured with */
    public function options(string $name): array
    {
        return $this->config[$name]['options'] ?? [];
    }

    /** @return list<string> the configured providers' names */
    public function names(): array
    {
        return array_keys($this->config);
    }

    /** @return list<string> the factories installed: what `factory:` may name */
    public function factories(): array
    {
        return array_keys($this->factories);
    }

    private function factory(string $name): ProviderFactoryInterface
    {
        $provider = $this->config[$name] ?? throw new InvalidConfigException(\sprintf('No "%s" film provider; configured: %s.', $name, implode(', ', array_keys($this->config)) ?: 'none'));

        return $this->factories[$provider['factory']] ?? throw new InvalidConfigException(\sprintf('No "%s" factory for the "%s" film provider; installed: %s.', $provider['factory'], $name, implode(', ', array_keys($this->factories)) ?: 'none'));
    }
}
