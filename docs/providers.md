# Writing a provider

A provider is a package `omnifilm/<name>`, namespace `Omnifilm\<Name>\`, requiring
`omniscient/omnifilm` and `symfony/http-client` (or the library it wraps, which must be
framework-free).

1. **The factory** extends `ProviderFactory`: `populate()` sets `omnifilm.factory_name`,
   `omnifilm.factory_title`, `omnifilm.required_options` and the defaults; `build()` makes the
   provider. Its constructor takes `?HttpClientInterface $http = null` - the application's client,
   a `MockHttpClient` or `RecordedHttpClient` in tests, `HttpClient::create()` when none.
2. **The provider** implements `ProviderInterface` and the capability interfaces it has.
   `capabilities()` is honest: the types, the registers per type, the attribution its terms ask
   for, the terms' URLs, whether it needs a key. `work()` throws `NotSupportedException::ref()` for
   a register it does not resolve and returns null for a work it does not know.
3. **Calls** go through `Http\JsonClient` (headers on every request, a 404 as null, errors as
   `ProviderException`/`UnreachableException`, no key in a message) and answers are read with
   `Http\Data` (`string()`, `int()`, `date()`, `list()`, `strings()`), so PHPStan at its highest
   level holds without trusting the provider's JSON.
4. **Tests**: recorded answers in `Tests/Fixtures` (captured from the service when it needs no
   key, trimmed; written after its reference otherwise, and said so), a `BareTest` extending
   `Testing\BareTestCase`, a `LiveTest` in the group `live` for a keyless service only.
5. **README**: options, the service's terms as read (attribution, limits, caching, commercial
   use), "Verified, and not".

## The providers' terms, in short

| Provider | Attribution | Limits and use |
|---|---|---|
| tmdb | "uses TMDB and the TMDB APIs but is not endorsed, certified, or otherwise approved by TMDB" + TMDB's logo; JustWatch for where to watch | no commercial use without an agreement; no cache older than 6 months |
| omdb | its data is CC BY-NC 4.0 | non-commercial; free keys 1,000 requests a day |
| tvdb | "Metadata provided by TheTVDB. Please consider adding missing information or subscribing." with a link | licensed (commercial or individual; discounts for open source) |
| trakt | credit and link | 1,000 GET calls every 5 minutes per application (documented rate limit) |
| wikidata | data CC0; Commons pictures under their own licences | a User-Agent naming the application and a contact; serial requests |
