---
title: Errors
order: 50
---

# Errors

Every exception Omnifilm throws implements `Omnifilm\Exception\OmnifilmException` (a `\Throwable`):

| Exception | Extends | When |
|---|---|---|
| `InvalidArgumentException` | `\InvalidArgumentException` | a value no record can hold: a search without words, an episode searched for, a limit outside 1-50, an empty identifier, a register name that is not one |
| `InvalidConfigException` | `\InvalidArgumentException` | a provider not configured, a factory not installed, a required option missing or empty, TMDB without token nor key, Wikidata's User-Agent naming no application |
| `NotSupportedException` | `\LogicException` | a provider asked for what it does not do: a contract it lacks (`Registry::availability()` on OMDb), a register it does not resolve for that type. Thrown before any call |
| `ProviderException` | `\RuntimeException` | the provider answered, and not with what was asked: a status of 400 or more but 404, a body that is not JSON, an error inside a 200 (OMDb's `"Response": "False"`, Wikidata's `error`), TheTVDB giving no token |
| `UnreachableException` | `\RuntimeException` | the provider could not be reached: a network error, a timeout |

Not an exception: a work, a series or a season the provider does not know - a 404 included - is
`null` (`work()`) or `[]` (`search()`, `episodes()`, `offers()`).

## The messages

Seen in the [harness](harness.md) on 2026-10-26, the network ones in a container with no network
(those three as they read since 2026-10-29, the address the HTTP client quotes left out):

```
omdb network       UnreachableException: OMDb could not be reached for GET /: Could not resolve host: www.omdbapi.com (Could not contact DNS servers).
tmdb network       UnreachableException: TMDB could not be reached for GET /3/movie/603: Could not resolve host: api.themoviedb.org (Could not contact DNS servers).
trakt network      UnreachableException: Trakt could not be reached for GET /movies/481: Could not resolve host: api.trakt.tv (Could not contact DNS servers).
omdb bad key       ProviderException: OMDb answered 401 for GET /. {"Response":"False","Error":"Invalid API key!"}
omdb limit         ProviderException: OMDb answered 401 for GET /. {"Response":"False","Error":"Request limit reached!"}
omdb html 503      ProviderException: OMDb answered 503 for GET /. Service Unavailable
omdb 200 html      ProviderException: OMDb answered 200 for GET /. The body is not JSON.
omdb tvdb ref      NotSupportedException: OMDb does not find a series by its tvdb identifier.
wikidata maxlag    ProviderException: Wikidata answered maxlag for query. Waiting for a database server: 8.1 seconds lagged.
wikidata no UA     InvalidConfigException: Wikidata needs a User-Agent naming the application and a way to reach its operator: "MySite/1.0 (https://example.org; films@example.org)".
tmdb no key        InvalidConfigException: The "tmdb" provider needs: token (the API Read Access Token) or api_key.
query empty        InvalidArgumentException: A search needs words.
query episode      InvalidArgumentException: An episode is not searched for: ask its series for its episodes.
query limit        InvalidArgumentException: A search answers 1 to 50 works.
ref empty          InvalidArgumentException: A work is named by a non-empty identifier.
ids bad            InvalidArgumentException: "IMDb" is not a register name.
registry avail     NotSupportedException: The "o" provider (Omnifilm\Omdb\OmdbProvider) does not say where to watch.
registry none      InvalidConfigException: No "x" film provider; configured: o.
```

The run also had `omdb too many`, OMDb's "Too many results." as a `ProviderException`; since
2026-10-29 it is an empty search (`[]`).

A `ProviderException` names the provider, the status (or what stood for it: `an error`, `maxlag`,
`no token`), the method and the path, then the answer's text: tags stripped, at most 300
characters. Its code is 0: the status is in the message only. The path never carries the query, so
a key sent there stays out of `ProviderException`s.

An `UnreachableException` names the method and the path too, then the HTTP client's reason without
the address it quotes (`JsonClient::reason()`): OMDb's `apikey` and TMDB's `api_key` stay out of
it. Its previous exception, the HTTP client's, keeps the client's own message, URL included: a log
that writes the whole chain writes it too.

## What to catch where

| Where | Catch |
|---|---|
| a page showing a record | `OmnifilmException`: show what the application kept, or the title alone; log the message privately |
| a search box | `InvalidArgumentException` for an empty query; `ProviderException` for a provider that refuses (OMDb's daily limit) |
| a chain of providers (`Registry::work()`) | an exception of one provider ends the chain: catch around each provider's `work()` to go on to the next |
| a batch import | `UnreachableException`, and `ProviderException` for a 429 or 5xx or Wikidata's `maxlag`: wait and retry; a 401: the key, to fix |
| building the registry, a deploy | `InvalidConfigException`: a configuration error, to fix, not to retry |
| a feature a provider may lack | check `capabilities()` (`contracts()`, `resolves()`) first; `NotSupportedException` is a programming error |
