<?php

namespace Omnifilm;

use Omnifilm\Model\Scheme;
use Omnifilm\Model\WorkRef;
use Omnifilm\Model\WorkType;

/**
 * What a provider is, said before it is asked anything: what it can do, the
 * registers it finds a work by, and what its terms ask of whoever shows its
 * records.
 */
final readonly class Capabilities
{
    /**
     * @param bool                      $search       it searches by title (SearchInterface)
     * @param bool                      $lookup       it gives a work's record by an identifier (LookupInterface)
     * @param bool                      $episodes     it lists a season's episodes (EpisodesInterface)
     * @param bool                      $availability it says where to watch (AvailabilityInterface)
     * @param list<WorkType>            $types        the types it knows
     * @param array<string, list<Scheme>> $lookupBy   per type value, the registers it finds a work by
     * @param bool                      $images       its records carry pictures
     * @param bool                      $credits      its records carry the cast and crew
     * @param ?string                   $attribution  what its terms ask to be shown beside its records
     * @param list<string>              $terms        the terms whoever runs it must follow (URLs)
     * @param bool                      $keyless      it needs no account nor key
     */
    public function __construct(
        public bool $search = false,
        public bool $lookup = false,
        public bool $episodes = false,
        public bool $availability = false,
        public array $types = [],
        public array $lookupBy = [],
        public bool $images = false,
        public bool $credits = false,
        public ?string $attribution = null,
        public array $terms = [],
        public bool $keyless = false,
    ) {
    }

    /** Whether LookupInterface::work() finds this work by this register. */
    public function resolves(WorkRef $ref): bool
    {
        return $this->lookup && \in_array($ref->scheme, $this->lookupBy[$ref->type->value] ?? [], true);
    }

    public function knows(WorkType $type): bool
    {
        return \in_array($type, $this->types, true);
    }

    /** @return list<string> what it can be asked, by contract: search, lookup, episodes, availability */
    public function contracts(): array
    {
        return array_keys(array_filter(['search' => $this->search, 'lookup' => $this->lookup, 'episodes' => $this->episodes, 'availability' => $this->availability]));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'contracts' => $this->contracts(),
            'types' => array_map(static fn (WorkType $type) => $type->value, $this->types),
            'lookupBy' => array_map(static fn (array $schemes) => array_map(static fn (Scheme $scheme) => $scheme->value, $schemes), $this->lookupBy),
            'images' => $this->images,
            'credits' => $this->credits,
            'attribution' => $this->attribution,
            'terms' => $this->terms,
            'keyless' => $this->keyless,
        ];
    }
}
