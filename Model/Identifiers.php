<?php

namespace Omnifilm\Model;

use Omnifilm\Exception\InvalidArgumentException;

/**
 * The identifiers of one work (or one person) in the registers that know it,
 * keyed by Scheme value: what lets a record read from one provider be
 * completed by another.
 */
final readonly class Identifiers
{
    /** @param array<string, string> $ids */
    private function __construct(private array $ids)
    {
    }

    /**
     * Empty values are dropped; keys are Scheme values, or a register of the
     * application's own (lower case letters, digits, "-" and "_").
     *
     * @param array<string, string|int|null> $ids
     */
    public static function of(array $ids): self
    {
        $kept = [];
        foreach ($ids as $scheme => $id) {
            if (!preg_match('~^[a-z0-9_-]+$~', $scheme)) {
                throw new InvalidArgumentException(\sprintf('"%s" is not a register name.', $scheme));
            }
            if (null !== $id && '' !== trim((string) $id)) {
                $kept[$scheme] = trim((string) $id);
            }
        }
        ksort($kept);

        return new self($kept);
    }

    public static function none(): self
    {
        return new self([]);
    }

    public function get(Scheme|string $scheme): ?string
    {
        return $this->ids[$scheme instanceof Scheme ? $scheme->value : $scheme] ?? null;
    }

    public function has(Scheme|string $scheme): bool
    {
        return null !== $this->get($scheme);
    }

    public function with(Scheme|string $scheme, string|int|null $id): self
    {
        return self::of([...$this->ids, ($scheme instanceof Scheme ? $scheme->value : $scheme) => $id]);
    }

    /** These identifiers, completed by the other's where these have none. */
    public function merge(self $other): self
    {
        return self::of($this->ids + $other->ids);
    }

    public function isEmpty(): bool
    {
        return [] === $this->ids;
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return $this->ids;
    }
}
