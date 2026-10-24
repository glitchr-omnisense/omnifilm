<?php

namespace Omnifilm;

/**
 * One source of works' records, configured: TMDB, Wikidata... Its
 * Capabilities say what it can be asked - search by title
 * (SearchInterface), a work by an identifier (LookupInterface), a season's
 * episodes (EpisodesInterface), where to watch (AvailabilityInterface) -
 * and what its terms ask of whoever shows its records.
 */
interface ProviderInterface
{
    /** The factory's name: "tmdb", "wikidata"... */
    public function getName(): string;

    public function getTitle(): string;

    public function capabilities(): Capabilities;
}
