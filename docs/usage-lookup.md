---
title: Records and identifiers
order: 32
---

# Records and identifiers

`LookupInterface::work(WorkRef $ref, ?string $language = null): ?Work` gives a work's whole record
by one of its identifiers. Every provider answers it, each for the registers listed in its
`capabilities()->lookupBy`.

```php
use Omnifilm\Model\ImageKind;
use Omnifilm\Model\Role;
use Omnifilm\Model\WorkRef;

$work = $films->lookup('tmdb')->work(WorkRef::imdb('tt0133093'), 'fr-FR');

if (null === $work) {
    // the provider does not know it
}
$work->title;                                  // in the language asked, where the provider translates
$work->image(ImageKind::Poster)?->url;         // shown from the provider's server, under its terms
$work->credits(Role::Director);                // list<Credit>
$work->identifiers->get('wikidata');           // the work in another register
```

## Not known, not supported

- `null`: the provider resolves the register but has no such work - or has it under another type
  (an IMDb number of a series asked as a film is `null`, not the series).
- `NotSupportedException`, before any call: the provider does not resolve that register for that
  type (`OMDb does not find a series by its tvdb identifier.`). Ask
  `$provider->capabilities()->resolves($ref)` first, or let `Registry::work()` choose.

## One record from several providers

`Registry::work()` asks the providers in turn - in the configuration's order, or in the order of
the names given - skipping those that cannot resolve the ref, and returns the first record found:

```php
use Omnifilm\Model\Scheme;
use Omnifilm\Model\WorkRef;
use Omnifilm\Model\WorkType;
use Omnifilm\Omdb\OmdbFactory;
use Omnifilm\Registry;
use Omnifilm\Wikidata\WikidataFactory;
use Symfony\Component\HttpClient\HttpClient;

$http = HttpClient::create(['timeout' => 30]);
$films = new Registry([new OmdbFactory($http), new WikidataFactory($http)], [
    'omdb' => ['factory' => 'omdb', 'options' => ['api_key' => 'not-used-here']],
    'open' => ['factory' => 'wikidata', 'options' => ['user_agent' => 'OmnisenseDocs/1.0 (https://github.com/glitchr-omnisense/omnifilm)']],
]);

// OMDb resolves IMDb numbers only: a TMDB series number goes on to Wikidata, OMDb is not called.
$series = $films->work(WorkRef::tmdb(1399, WorkType::Series), 'en');
echo $series->source, ': ', $series->title, ' (', $series->year, ")\n";
echo json_encode($series->identifiers->toArray()), "\n";
echo 'at OMDb it would be: ', $series->ref(Scheme::Imdb), "\n";
```

```
$ php chain.php
wikidata: Game of Thrones (2011)
{"imdb":"tt0944947","tmdb":"1399","tvdb":"121361","wikidata":"Q23572"}
at OMDb it would be: series:imdb:tt0944947
```

(run live on 2026-10-26, 15:13 UTC, in the harness: three calls to Wikidata, none to OMDb)

`$work->ref(Scheme::X)` turns an identifier the record carries into the key to another provider:
the usual chain is a keyless register first (Wikidata), then the provider whose fields the page
needs (TMDB's pictures, OMDb's scores):

```php
$open = $films->work(WorkRef::imdb('tt0079944'), 'fr', 'open');
$pictures = null !== ($ref = $open?->ref(Scheme::Tmdb)) ? $films->work($ref, 'fr', 'tmdb') : null;
```

Records are not merged: each `Work` is one provider's, its `source` says which. To complete one with
another, take the fields the page needs from each, and `Identifiers::merge()` for the identifiers:

```php
$ids = $open->identifiers->merge($pictures->identifiers);   // these first, completed by the other's
```

An exception of a provider (unreachable, a refused key) ends `Registry::work()`: catch
`OmnifilmException` around it, or around each provider's own `work()` for a chain that goes on.

## What each provider does

| | Resolves | Calls | Language | Notable fields |
|---|---|---|---|---|
| tmdb | film: tmdb, imdb, wikidata; series: tmdb, imdb, tvdb, wikidata; episode: imdb, tvdb | one by TMDB number (`append_to_response=credits,external_ids`); `/find` first otherwise | translated (`language`, `fr-FR`) | credits (crew: director, writing, producer, composer; cast up to `cast`), poster and backdrop, TMDB rating, IMDb, TheTVDB and Wikidata numbers, countries and languages as codes, a series' number of seasons |
| omdb | imdb (film, series, episode) | one (`?i=...&plot=full`) | ignored | IMDb, Rotten Tomatoes and Metacritic scores, director, writers (a part in parentheses as the job: `creator`), actors, poster; countries and languages as names |
| tvdb | film, series: tvdb, imdb, tmdb; episode: tvdb, imdb | the login once; `/search/remoteid/{id}` first when not a TheTVDB number; then the extended record | the record's translations (three-letter codes) | people by type (director, writer, actor, producer, creator, composer), poster, the remote identifiers, genres, a series' official seasons |
| trakt | film: trakt, imdb, tmdb; series, episode: trakt, imdb, tmdb, tvdb | `/search/{scheme}/{id}` first when not a Trakt number; the record (`extended=full`) and `/people` | ignored | Trakt, IMDb, TMDB, TheTVDB numbers; crew (directing, writing, producers, composers), cast up to `cast`; Trakt rating; no pictures |
| wikidata | film, series: wikidata, imdb, tmdb, tvdb; episode: wikidata, imdb, tvdb | `haswbstatement` search first when not a Q-number; the entity; its labels (one call per 50) | the labels' language (`fr-FR` is asked as `fr`) | the identifiers Wikidata keeps, genres, countries and languages as labels, director, writers, cast (30 at most, with characters), producers, composers, Commons poster, logo and image |

The providers' manuals give the details: which property or field each value is read from, what is
left out.
