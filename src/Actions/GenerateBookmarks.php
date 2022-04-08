<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Actions;

use Weph\ObsidianTools\Markdown\Table;
use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\Query;
use Weph\ObsidianTools\Vault\Vault;

final class GenerateBookmarks implements Action
{
    public function __construct(private readonly Vault $vault)
    {
    }

    public function run(): void
    {
        $query = Query::create()->withContent('/#bookmark/');

        $table = new Table(['Quelle', 'Link', 'Beschreibung', 'Tags']);

        $pattern = '/^- (?P<link>\[.+\]\(.+\)): (?P<description>[^#]+)(?P<tags>#.+)/m';
        foreach ($this->vault->notesMatching($query) as $matchedNote) {
            preg_match_all($pattern, $matchedNote->note->content, $matches, PREG_SET_ORDER);

            foreach ($matches as $x) {
                $tags        = explode(' ', $x['tags']);
                $bookmarkPos = array_search('#bookmark', $tags);
                if ($bookmarkPos === false) {
                    continue;
                }
                unset($tags[$bookmarkPos]);

                $source = sprintf('[[%s]]', $matchedNote->note->name);

                $table->addRow([$source, $x['link'], trim($x['description']), implode(' ', $tags)]);
            }
        }

        $content = "# Bookmarks\n\n";
        $content .= $table->render();

        $this->vault->save(new Note('Notes/Bookmarks.md', ['parent' => '[[Index]]', 'tags' => ['generated']], $content));
    }
}
