<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Vault;

interface Vault
{
    /**
     * @return list<Note|Asset>
     */
    public function all(): array;

    /**
     * @throws NoteNotFound
     */
    public function get(string $location): Note|Asset;

    public function save(Note|Asset $note): void;

    /**
     * @return list<MatchedNote>
     */
    public function notesMatching(Query $query): array;
}
