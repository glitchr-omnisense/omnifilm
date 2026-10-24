<?php

namespace Omnifilm\Tests;

use Omnifilm\Http\Data;
use Omnifilm\Registry;
use Omnifilm\Testing\BareTestCase;

/**
 * omniscient/omnifilm with no framework: the harness's bare script searches,
 * looks up, lists episodes and offers, and makes a provider's JSON call,
 * in a PHP process of its own - nothing may load from outside the package
 * but Symfony's HTTP client contracts (interfaces only).
 */
final class BareTest extends BareTestCase
{
    protected static function entryClass(): string
    {
        return Registry::class;
    }

    protected static function requiredPackages(): array
    {
        return ['symfony/http-client-contracts'];
    }

    public function testRecordsReadByHandLoadNothingButOmnifilmAndWhatItRequires(): void
    {
        $report = Data::of(self::php([__DIR__.'/../docker/harness/bin/bare']));

        self::assertSame(['search', 'lookup', 'episodes', 'availability'], $report->get('capabilities.contracts'));
        self::assertSame(['Stalker'], $report->get('search'));
        $work = $report->data('work');
        self::assertSame(['Stalker', 'Сталкер', 1979, '1979-05-25', 162], [$work->get('title'), $work->get('originalTitle'), $work->get('year'), $work->get('released'), $work->get('runtime')]);
        self::assertSame(['imdb' => 'tt0079944', 'wikidata' => 'Q200437'], $work->get('identifiers'));
        self::assertSame(['Andrei Tarkovsky'], $report->get('directors'));
        self::assertSame([[1, 'Pilot'], [2, 'Traces to Nowhere']], $report->get('episodes'));
        self::assertSame([['kind' => 'stream', 'service' => 'Example Films', 'country' => 'FR', 'url' => 'https://example.org/stalker', 'logo' => null]], $report->get('offers'));
        self::assertSame(['Stalker', '1979', ['Anatoly Solonitsyn'], 'omnifilm-bare/1.0'], $report->get('json'));
        self::assertNull($report->get('json-404'));
        self::assertSame('Example answered 500 for GET /works/3. Internal error', $report->get('json-error'));

        $symbols = $report->strings('loaded.symbols');
        self::assertContains('Omnifilm\Testing\FixedProvider', $symbols);
        self::assertSame([], self::foreign($symbols), 'no class of a framework, an HTTP client or an application');
        self::assertSame([], self::outside($report->strings('loaded.files'), $report->strings('loaded.eager')), 'no file but the package and what it requires');
    }
}
