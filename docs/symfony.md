---
title: Symfony
order: 15
---

# Symfony

There is no Omnifilm bundle, and omnibridge/symfony has no Omnifilm wiring yet: a Symfony
application declares the registry as a service of its own. Every factory takes the HTTP client as
its one constructor argument (`?Symfony\Contracts\HttpClient\HttpClientInterface`), which
autowiring fills with the application's `http_client` service.

## The registry as a service

```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true

    Omnifilm\Wikidata\WikidataFactory: ~
    Omnifilm\Tmdb\TmdbFactory: ~

    Omnifilm\Registry:
        arguments:
            $factories:
                - '@Omnifilm\Wikidata\WikidataFactory'
                - '@Omnifilm\Tmdb\TmdbFactory'
            $config:
                open:
                    factory: wikidata
                    options:
                        user_agent: 'MySite/1.0 (https://example.org; films@example.org)'
                        language: fr
                tmdb:
                    factory: tmdb
                    options:
                        token: '%env(TMDB_TOKEN)%'
                        language: fr-FR
```

```sh
# .env.local, or bin/console secrets:set
TMDB_TOKEN=...
```

The order of `$config` is the order `Registry::work()` asks the providers in when no name is given:
put the one that should answer first first (the keyless one, or the one whose terms suit the
page).

A tag gathers the factories instead of listing them:

```yaml
services:
    _instanceof:
        Omnifilm\ProviderFactoryInterface:
            tags: ['omnifilm.factory']

    Omnifilm\Registry:
        arguments:
            $factories: !tagged_iterator omnifilm.factory
            $config: '%omnifilm.providers%'
```

## Using it

```php
use Omnifilm\Model\WorkRef;
use Omnifilm\Registry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class FilmSheet
{
    public function __construct(private readonly Registry $films, private readonly CacheInterface $cache)
    {
    }

    /** @return ?array<string, mixed> */
    public function byImdb(string $imdb): ?array
    {
        return $this->cache->get('film.'.$imdb, function (ItemInterface $item) use ($imdb): ?array {
            $item->expiresAfter(30 * 86400);     // within TMDB's six months

            return $this->films->work(WorkRef::imdb($imdb), 'fr')?->toArray();
        });
    }
}
```

Nothing is built when the container compiles: a provider is built, and its required options
checked, the first time the registry is asked for it, then kept. Records are not cached by
Omnifilm: the application's cache keeps them, within each source's terms. `Work::toArray()` gives a
plain array to keep; a record is rebuilt from the provider, not from that array (there is no
`fromArray()`).

There is no command, event, Twig function or route. Show each provider's
`capabilities()->attribution` beside its records, as its terms ask: `Work::$source` names the
provider a record came from.

This page was written from the classes' constructors; it was not run in a Symfony kernel.
