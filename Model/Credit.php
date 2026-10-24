<?php

namespace Omnifilm\Model;

/** One person's part in a work: who, as what, and the character played. */
final readonly class Credit
{
    public Identifiers $identifiers;

    /** @param ?int $order the billing order among the cast, from 0 */
    public function __construct(
        public string $name,
        public Role $role,
        public ?string $character = null,
        public ?int $order = null,
        ?Identifiers $identifiers = null,
        public ?string $job = null,
    ) {
        $this->identifiers = $identifiers ?? Identifiers::none();
    }

    /** @return array{name: string, role: string, character: ?string, order: ?int, job: ?string, identifiers: array<string, string>} */
    public function toArray(): array
    {
        return ['name' => $this->name, 'role' => $this->role->value, 'character' => $this->character, 'order' => $this->order, 'job' => $this->job, 'identifiers' => $this->identifiers->toArray()];
    }
}
