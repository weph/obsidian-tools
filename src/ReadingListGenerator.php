<?php
declare(strict_types=1);

namespace Weph\ObsidianTools;

use Weph\ObsidianTools\Markdown\Table;
use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\Query;
use Weph\ObsidianTools\Vault\Vault;

final class ReadingListGenerator
{
    public function __construct(private readonly Vault $vault)
    {
    }

    public function run(): void
    {
        $query = Query::create()->withLocation('Notes/Quellen/Bücher');

        $table = new Table(['Titel']);
        foreach ($this->vault->notesMatching($query) as $matchingNote) {
            $noteName = str_replace('.md', '', basename($matchingNote->note->path));
            $link     = sprintf('[[%s]]', $noteName);

            $table->addRow([$link]);
        }

        $frontMatter = ['parent' => '[[Index]]', 'tags' => ['generated']];
        $content     = "# Leseliste\n\n" . $table->render();

        $this->vault->save(new Note('Notes/Leseliste.md', $frontMatter, $content));
    }
}
