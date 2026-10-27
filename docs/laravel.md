---
title: Laravel
order: 16
---

# Laravel

omnibridge/laravel, the bridge that hosts omnibase in Laravel, has no Omnifilm provider yet. The
registry is plain PHP: a service provider of the application's builds it once, from a config file,
with a `symfony/http-client` client (every provider requires that package; Laravel's own HTTP
client is Guzzle's, which the factories do not take).

```php
// config/omnifilm.php
return [
    'providers' => [
        'open' => ['factory' => 'wikidata', 'options' => [
            'user_agent' => 'MySite/1.0 (https://example.org; films@example.org)',
        ]],
        'tmdb' => ['factory' => 'tmdb', 'options' => [
            'token' => env('TMDB_TOKEN'),
            'language' => 'fr-FR',
        ]],
    ],
];
```

```php
// app/Providers/OmnifilmServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Omnifilm\Registry;
use Omnifilm\Tmdb\TmdbFactory;
use Omnifilm\Wikidata\WikidataFactory;
use Symfony\Component\HttpClient\HttpClient;

final class OmnifilmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Registry::class, function (): Registry {
            $http = HttpClient::create(['timeout' => 20]);

            return new Registry([
                new WikidataFactory($http),
                new TmdbFactory($http),
            ], config('omnifilm.providers'));
        });
    }
}
```

Register the provider (`bootstrap/providers.php` on Laravel 11 and later), then inject the
registry where it is needed:

```php
use Illuminate\Support\Facades\Cache;
use Omnifilm\Model\WorkRef;
use Omnifilm\Registry;

final class FilmController
{
    public function show(Registry $films, string $imdb)
    {
        $work = Cache::remember('film.'.$imdb, now()->addDays(30), fn () => $films->work(WorkRef::imdb($imdb), 'fr')?->toArray());

        return view('films.show', ['work' => $work]);
    }
}
```

List in the provider every factory the application uses: a hand-built registry knows only the
factories it is given. Configuration caching (`php artisan config:cache`) keeps working: the
options are plain arrays read through `config()`. This page was written from the registry's API;
it was not run in a Laravel application.
