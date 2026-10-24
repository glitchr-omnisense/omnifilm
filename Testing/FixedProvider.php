<?php

namespace Omnifilm\Testing;

use Omnifilm\AvailabilityInterface;
use Omnifilm\Capabilities;
use Omnifilm\EpisodesInterface;
use Omnifilm\Exception\NotSupportedException;
use Omnifilm\LookupInterface;
use Omnifilm\Model\Offer;
use Omnifilm\Model\Query;
use Omnifilm\Model\Scheme;
use Omnifilm\Model\Work;
use Omnifilm\Model\WorkRef;
use Omnifilm\Model\WorkType;
use Omnifilm\ProviderInterface;
use Omnifilm\SearchInterface;

/**
 * A provider that answers from the works it was given, with no network: an
 * application's tests, the harness's bare script. It finds a work by any
 * identifier the work carries, searches titles by their words, gives a
 * season's episodes from the works whose series carries the identifier, and
 * the offers given per "type:scheme:id" and country.
 */
final class FixedProvider implements ProviderInterface, SearchInterface, LookupInterface, EpisodesInterface, AvailabilityInterface
{
    /**
     * @param list<Work>                                $works
     * @param array<string, array<string, list<Offer>>> $offers by (string) WorkRef, then country
     */
    public function __construct(private readonly array $works = [], private readonly array $offers = [], private readonly string $name = 'fixed')
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return 'Fixed works';
    }

    public function capabilities(): Capabilities
    {
        $all = Scheme::cases();

        return new Capabilities(search: true, lookup: true, episodes: true, availability: true, types: WorkType::cases(), lookupBy: ['movie' => $all, 'series' => $all, 'episode' => $all], images: true, credits: true, keyless: true);
    }

    public function search(Query $query): array
    {
        $words = preg_split('~\W+~u', mb_strtolower($query->text), -1, \PREG_SPLIT_NO_EMPTY) ?: [];
        $found = array_filter($this->works, static function (Work $work) use ($query, $words): bool {
            $title = mb_strtolower($work->title.' '.$work->originalTitle);

            return WorkType::Episode !== $work->type
                && (null === $query->type || $query->type === $work->type)
                && (null === $query->year || $query->year === $work->year)
                && [] === array_filter($words, static fn (string $word) => !str_contains($title, $word));
        });

        return \array_slice(array_values($found), 0, $query->limit);
    }

    public function work(WorkRef $ref, ?string $language = null): ?Work
    {
        if (!$this->capabilities()->resolves($ref)) {
            throw NotSupportedException::ref($this->getTitle(), $ref);
        }
        foreach ($this->works as $work) {
            if ($ref->type === $work->type && $ref->id === $work->identifiers->get($ref->scheme)) {
                return $work;
            }
        }

        return null;
    }

    public function episodes(WorkRef $series, int $season, ?string $language = null): array
    {
        $episodes = array_filter($this->works, static fn (Work $work) => WorkType::Episode === $work->type && $season === $work->season && $series->id === $work->series?->get($series->scheme));
        usort($episodes, static fn (Work $a, Work $b) => $a->number <=> $b->number);

        return $episodes;
    }

    public function offers(WorkRef $ref, string $country): array
    {
        return $this->offers[(string) $ref][strtoupper($country)] ?? [];
    }
}
