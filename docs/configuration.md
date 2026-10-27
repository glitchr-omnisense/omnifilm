---
title: Configuration reference
order: 40
---

# Configuration reference

## The registry's configuration

`new Registry($factories, $config)` takes the providers by name, each with its factory and options:

```php
[
    '<provider name>' => [
        'factory' => '<factory name>',     // required: a factory's getName()
        'options' => [/* ... */],          // optional, default []: handed to the factory's create()
    ],
]
```

The provider name is the application's (`open`, `tmdb`, `scores`); the factory name is the
package's. The order matters to `Registry::work()` called without names: it asks the providers in
this order. Options are checked when the provider is first asked for: a required option missing,
`''` or `[]` is an `InvalidConfigException` naming it.

Every factory takes an optional HTTP client in its constructor - `new TmdbFactory($http)` - and
makes its own (`HttpClient::create(['timeout' => 20])`) when given none.

## omnifilm/tmdb

Factory `Omnifilm\Tmdb\TmdbFactory`, name `tmdb`.

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `token` | string | `null` | one of the two | the API Read Access Token, sent as `Authorization: Bearer`; preferred when both are given |
| `api_key` | string | `null` | one of the two | the v3 API key, sent in the query string (`api_key=`) |
| `language` | string | `en-US` | no | the language of titles, overviews and genres when a call names none |
| `images` | string | `https://image.tmdb.org/t/p/` | no | the image server pictures' addresses are built on |
| `poster` | string | `w500` | no | the posters' size |
| `backdrop` | string | `w1280` | no | the backdrops' size |
| `still` | string | `w300` | no | the episodes' stills' size |
| `logo` | string | `w92` | no | the services' logos' size (where to watch) |
| `cast` | int | `20` | no | the cast members kept, in billing order |

With neither `token` nor `api_key`: `InvalidConfigException` (`The "tmdb" provider needs: token
(the API Read Access Token) or api_key.`).

## omnifilm/omdb

Factory `Omnifilm\Omdb\OmdbFactory`, name `omdb`.

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `api_key` | string | - | yes | the OMDb key, sent in the query string (`apikey=`) |
| `api` | string | `https://www.omdbapi.com/` | no | the API's address |

## omnifilm/tvdb

Factory `Omnifilm\Tvdb\TvdbFactory`, name `tvdb`.

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `api_key` | string | - | yes | the project's v4 key |
| `pin` | string | `null` | no | a subscriber's PIN, for a user-supported key |
| `language` | string | `null` | no | `fr` or `fra` (TheTVDB's codes are three letters; sixteen two-letter codes are mapped, `fr-FR` read as `fr`); `null`: TheTVDB's own texts |
| `api` | string | `https://api4.thetvdb.com/v4` | no | the API's base URL |

## omnifilm/trakt

Factory `Omnifilm\Trakt\TraktFactory`, name `trakt`.

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `client_id` | string | - | yes | the application's client id, sent as `trakt-api-key` |
| `user_agent` | string | - | yes | a User-Agent naming the application: `MySite/1.0 (+https://example.org)` |
| `api` | string | `https://api.trakt.tv` | no | the API's base URL |
| `cast` | int | `20` | no | the cast members kept |

## omnifilm/wikidata

Factory `Omnifilm\Wikidata\WikidataFactory`, name `wikidata`.

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `user_agent` | string | - | yes | naming the application and a way to reach its operator: `MySite/1.0 (https://example.org; films@example.org)`; one without a `/` or a `(` is refused |
| `language` | string | `en` | no | the labels' language when a call names none (`fr-FR` read as `fr`; `pt-br`, `zh-hans`, `zh-hant`, `en-gb`, `de-ch` kept) |
| `api` | string | `https://www.wikidata.org/w/api.php` | no | the MediaWiki API: Wikidata's, or another Wikibase's |
| `maxlag` | int | `0` | no | `0`: not sent; a number of seconds: sent, and Wikidata refuses reads while its servers lag more (for batch jobs, as Wikimedia asks) |

## Per call

What a call covers is its arguments: the `Query` (words, type, year, language, limit), the
`WorkRef`, the `language` of `work()` and `episodes()`, the season, the country of `offers()`
([Models](models.md)). A `language` given to a call replaces the provider's `language` option for
that call.

## The fixed provider (testing)

`Omnifilm\Testing\FixedProviderFactory`, name `fixed`, takes its records and offers in its
constructor (`new FixedProviderFactory($works, $offers)`) and no option.
