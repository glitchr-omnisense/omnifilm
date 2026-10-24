<?php

namespace Omnifilm\Tests;

use Omnifilm\Capabilities;
use Omnifilm\Exception\InvalidArgumentException;
use Omnifilm\Model\Credit;
use Omnifilm\Model\Identifiers;
use Omnifilm\Model\Image;
use Omnifilm\Model\ImageKind;
use Omnifilm\Model\Query;
use Omnifilm\Model\Role;
use Omnifilm\Model\Scheme;
use Omnifilm\Model\Work;
use Omnifilm\Model\WorkRef;
use Omnifilm\Model\WorkType;
use PHPUnit\Framework\TestCase;

final class ModelTest extends TestCase
{
    public function testIdentifiersDropEmptyValuesAndMerge(): void
    {
        $ids = Identifiers::of(['tmdb' => 278, 'imdb' => ' tt0111161 ', 'tvdb' => null, 'trakt' => '']);

        self::assertSame(['imdb' => 'tt0111161', 'tmdb' => '278'], $ids->toArray());
        self::assertSame('278', $ids->get(Scheme::Tmdb));
        self::assertFalse($ids->has(Scheme::Wikidata));
        self::assertSame(['imdb' => 'tt0111161', 'tmdb' => '278', 'wikidata' => 'Q172241'], $ids->merge(Identifiers::of(['tmdb' => '1', 'wikidata' => 'Q172241']))->toArray());
        self::assertSame('Q1', $ids->with(Scheme::Wikidata, 'Q1')->get('wikidata'));
        self::assertTrue(Identifiers::none()->isEmpty());

        $this->expectException(InvalidArgumentException::class);
        Identifiers::of(['IMDb' => 'tt0111161']);
    }

    public function testAWorkRefNamesTypeRegisterAndIdentifier(): void
    {
        self::assertSame('movie:imdb:tt0111161', (string) WorkRef::imdb('tt0111161'));
        self::assertSame('series:tmdb:1399', (string) WorkRef::tmdb(1399, WorkType::Series));
        self::assertSame('series:tvdb:121361', (string) WorkRef::tvdb(121361));

        $this->expectException(InvalidArgumentException::class);
        WorkRef::wikidata(' ');
    }

    public function testAQueryNeedsWordsAndNoEpisode(): void
    {
        self::assertSame('Stalker', (new Query('  Stalker '))->text);
        foreach ([[' '], ['Pilot', WorkType::Episode], ['Stalker', null, null, null, 51]] as $arguments) {
            try {
                new Query(...$arguments);
                self::fail('Refused: '.json_encode($arguments));
            } catch (InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testAWorkGivesItsRefsPicturesAndPeople(): void
    {
        $work = new Work(WorkType::Movie, 'Stalker', Identifiers::of(['imdb' => 'tt0079944']), 'test', released: new \DateTimeImmutable('1979-05-25'), images: [new Image(ImageKind::Backdrop, 'https://example.org/b.jpg'), new Image(ImageKind::Poster, 'https://example.org/p.jpg', 500, 750)], credits: [new Credit('Andrei Tarkovsky', Role::Director, identifiers: Identifiers::of(['imdb' => 'nm0001789'])), new Credit('Alisa Freindlich', Role::Cast, 'Wife', 1)]);

        self::assertSame('movie:imdb:tt0079944', (string) $work->ref(Scheme::Imdb));
        self::assertNull($work->ref(Scheme::Tmdb));
        self::assertSame('https://example.org/p.jpg', $work->image()?->url);
        self::assertNull($work->image(ImageKind::Still));
        self::assertSame(['Alisa Freindlich'], array_map(static fn (Credit $c) => $c->name, $work->credits(Role::Cast)));
        $array = $work->toArray();
        self::assertSame('1979-05-25', $array['released']);
        self::assertSame(['name' => 'Andrei Tarkovsky', 'role' => 'director', 'character' => null, 'order' => null, 'job' => null, 'identifiers' => ['imdb' => 'nm0001789']], $work->credits[0]->toArray());
    }

    public function testCapabilitiesSayWhatResolves(): void
    {
        $capabilities = new Capabilities(search: true, lookup: true, types: [WorkType::Movie, WorkType::Series], lookupBy: ['movie' => [Scheme::Imdb, Scheme::Tmdb], 'series' => [Scheme::Tmdb]]);

        self::assertTrue($capabilities->resolves(WorkRef::imdb('tt0111161')));
        self::assertFalse($capabilities->resolves(WorkRef::imdb('tt0944947', WorkType::Series)));
        self::assertTrue($capabilities->knows(WorkType::Series));
        self::assertFalse($capabilities->knows(WorkType::Episode));
        self::assertSame(['search', 'lookup'], $capabilities->contracts());
        self::assertSame(['movie' => ['imdb', 'tmdb'], 'series' => ['tmdb']], $capabilities->toArray()['lookupBy']);
        self::assertFalse((new Capabilities(lookupBy: ['movie' => [Scheme::Imdb]]))->resolves(WorkRef::imdb('tt0111161')), 'no lookup, nothing resolves');
    }
}
