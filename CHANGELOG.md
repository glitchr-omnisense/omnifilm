# Changelog

## Unreleased (1.x)

### Fixed
- An `UnreachableException`'s chain no longer holds the key. Its previous exception was the HTTP
  client's own, whose message quotes the whole URL (OMDb's `apikey=`, TMDB's `api_key=`), and
  every trace kept the arguments of the calls under it, the request's query and headers among
  them. The previous is now a copy of the client's exception (`Http\Redaction::copy()`: the same
  class and code, the key's parameters, the URL's user part and the request's secrets masked as
  `***`), and no trace of the chain keeps its arguments (`Redaction::withoutArguments()`).
- An `UnreachableException` no longer quotes the URL called: as its docblock promised, the message
  names the method and the path, then the HTTP client's reason without the address it quotes.
  Symfony's transport messages end with the whole URL, so OMDb's `apikey=` and TMDB's `api_key=`
  stood in the message.

### Added
- `Omnifilm\Http\Redaction`: a request's secrets (`secrets()`), a text without them (`text()`),
  a failure's copy to wrap in its place (`copy()`), a trace without arguments
  (`withoutArguments()`).
- `ext-mbstring` in `composer.json`'s requirements: `ProviderException` shortens error bodies with
  `mb_substr()`, which a PHP built without mbstring did not have (no test: a requirement).
- `Omnifilm\Http\JsonClient::reason()`: an HTTP client's message without the addresses it quotes.
