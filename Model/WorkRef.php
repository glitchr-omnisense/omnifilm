<?php

namespace Omnifilm\Model;

use Omnifilm\Exception\InvalidArgumentException;

/**
 * A work named by one identifier: its type, the register, the identifier in
 * it. WorkRef::imdb('tt0111161'), WorkRef::tmdb(1399, WorkType::Series).
 */
final readonly class WorkRef implements \Stringable
{
    public string $id;

    public function __construct(public WorkType $type, public Scheme $scheme, string|int $id)
    {
        $id = trim((string) $id);
        if ('' === $id) {
            throw new InvalidArgumentException('A work is named by a non-empty identifier.');
        }
        $this->id = $id;
    }

    public static function imdb(string $id, WorkType $type = WorkType::Movie): self
    {
        return new self($type, Scheme::Imdb, $id);
    }

    public static function tmdb(string|int $id, WorkType $type = WorkType::Movie): self
    {
        return new self($type, Scheme::Tmdb, $id);
    }

    public static function tvdb(string|int $id, WorkType $type = WorkType::Series): self
    {
        return new self($type, Scheme::Tvdb, $id);
    }

    public static function trakt(string|int $id, WorkType $type = WorkType::Movie): self
    {
        return new self($type, Scheme::Trakt, $id);
    }

    public static function wikidata(string $id, WorkType $type = WorkType::Movie): self
    {
        return new self($type, Scheme::Wikidata, $id);
    }

    /** "movie:imdb:tt0111161" */
    public function __toString(): string
    {
        return $this->type->value.':'.$this->scheme->value.':'.$this->id;
    }
}
