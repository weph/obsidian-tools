<?php
declare(strict_types=1);

namespace Weph\ObsidianTools;

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
}
