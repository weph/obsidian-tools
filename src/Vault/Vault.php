<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Vault;

interface Vault
{
    /**
     * @return list<Note>
     */
    public function all(): array;

    /**
     * @throws NoteNotFound
     */
    public function get(string $location): Note;

    public function save(Note $note): void;

    /**
     * @return list<MatchedNote>
     */
    public function notesMatching(Query $query): array;
}
