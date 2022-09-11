<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Actions;

use Weph\ObsidianTools\Markdown\Table;
use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\Query;
use Weph\ObsidianTools\Vault\Vault;

final class GenerateReadingList implements Action
{
    public function __construct(private readonly Vault $vault)
    {
    }

    public function run(): void
    {
        $query = Query::create()
            ->withContent('/(.*(?:\[\[(?P<link>.+)\]\]|"(?P<title>.+)").*#(?:audio)?book\/(?:started|finished))/');

        $books = [];

        foreach ($this->bookNotes() as $book) {
            $books[$book] = ['title' => $book, 'link' => true];
        }

        foreach ($this->vault->notesMatching($query) as $matchingNote) {
            foreach ($matchingNote->matches as $match) {
                if ($match['link']) {
                    $title = $match['link'];
                    $link  = true;
                } else {
                    $title = $match['title'];
                    $link  = false;
                }

                $books[$title] = ['title' => $title, 'link' => $link];
            }
        }

        ksort($books);

        $table = new Table(['Titel']);
        foreach ($books as $book) {
            if ($book['link']) {
                $link = sprintf('[[%s]]', $book['title']);
            } else {
                $link = $book['title'];
            }

            $table->addRow([$link]);
        }

        $frontMatter = ['parent' => '[[Index]]', 'tags' => ['generated']];
        $content     = "# Leseliste\n\n" . $table->render();

        $this->vault->save(new Note('Notes/Leseliste.md', $frontMatter, $content));
    }

    /**
     * @return iterable<string>
     */
    private function bookNotes(): iterable
    {
        $query = Query::create()->withLocation('Notes/Quellen/Bücher');

        foreach ($this->vault->notesMatching($query) as $matchingNote) {
            yield $matchingNote->note->name;
        }
    }
}
