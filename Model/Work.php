<?php

namespace Omnifilm\Model;

/**
 * The record of a work as one provider knows it: its titles, dates, people,
 * pictures and identifiers. What a provider does not give is empty or null,
 * never guessed: a search answer is usually shorter than a lookup.
 */
final readonly class Work
{
    /**
     * @param string       $source    the provider's name ("tmdb", "wikidata"...)
     * @param ?int         $runtime   minutes
     * @param list<string> $genres    as the provider names them, in the language asked when it translates
     * @param list<string> $countries ISO 3166-1 alpha-2 codes where the provider gives them, names otherwise
     * @param list<string> $languages ISO 639-1 codes where the provider gives them, names otherwise
     * @param list<Image>  $images
     * @param list<Credit> $credits
     * @param list<Rating> $ratings
     * @param ?string      $url       the work's page at the provider
     * @param ?Identifiers $series    an episode's series
     * @param ?int         $season    an episode's season (0: specials), or a series' number of seasons
     * @param ?int         $number    an episode's number in its season
     */
    public function __construct(
        public WorkType $type,
        public string $title,
        public Identifiers $identifiers,
        public string $source,
        public ?string $originalTitle = null,
        public ?int $year = null,
        public ?\DateTimeImmutable $released = null,
        public ?int $runtime = null,
        public ?string $overview = null,
        public array $genres = [],
        public array $countries = [],
        public array $languages = [],
        public array $images = [],
        public array $credits = [],
        public array $ratings = [],
        public ?string $url = null,
        public ?Identifiers $series = null,
        public ?int $season = null,
        public ?int $number = null,
    ) {
    }

    /** The work named in one register, when this record knows its identifier there. */
    public function ref(Scheme $scheme): ?WorkRef
    {
        $id = $this->identifiers->get($scheme);

        return null === $id ? null : new WorkRef($this->type, $scheme, $id);
    }

    public function image(ImageKind $kind = ImageKind::Poster): ?Image
    {
        foreach ($this->images as $image) {
            if ($kind === $image->kind) {
                return $image;
            }
        }

        return null;
    }

    /** @return list<Credit> */
    public function credits(Role $role): array
    {
        return array_values(array_filter($this->credits, static fn (Credit $credit) => $role === $credit->role));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'title' => $this->title,
            'originalTitle' => $this->originalTitle,
            'year' => $this->year,
            'released' => $this->released?->format('Y-m-d'),
            'runtime' => $this->runtime,
            'overview' => $this->overview,
            'genres' => $this->genres,
            'countries' => $this->countries,
            'languages' => $this->languages,
            'identifiers' => $this->identifiers->toArray(),
            'images' => array_map(static fn (Image $image) => $image->toArray(), $this->images),
            'credits' => array_map(static fn (Credit $credit) => $credit->toArray(), $this->credits),
            'ratings' => array_map(static fn (Rating $rating) => $rating->toArray(), $this->ratings),
            'url' => $this->url,
            'series' => $this->series?->toArray(),
            'season' => $this->season,
            'number' => $this->number,
            'source' => $this->source,
        ];
    }
}
