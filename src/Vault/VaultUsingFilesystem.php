<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Vault;

use Ergebnis\FrontMatter\Parser;
use Ergebnis\FrontMatter\YamlParser;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

final class VaultUsingFilesystem implements Vault
{
    public function __construct(
        private readonly string $path,
        private readonly Parser $frontMatterParser,
        private readonly Finder $finder)
    {
    }

    public static function atPath(string $path): self
    {
        return new self($path, new YamlParser(), new Finder());
    }

    public function all(): array
    {
        $files = $this->finder->files()->in($this->path);

        $result = [];
        foreach ($files as $file) {
            $result[] = $this->noteAt($file->getPathname());
        }

        return $result;
    }

    public function get(string $location): Note|Asset
    {
        $absolutePath = $this->path . '/' . $location;

        if (!file_exists($absolutePath)) {
            throw NoteNotFound::atLocation($location);
        }

        return $this->noteAt($absolutePath);
    }

    public function save(Note|Asset $note): void
    {
        $absolutePath = $this->path . '/' . $note->path;
        $directory    = dirname($absolutePath);

        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        if ($note instanceof Asset) {
            file_put_contents($absolutePath, $note->content);

            return;
        }

        file_put_contents($absolutePath, $this->noteContent($note));
    }

    public function notesMatching(Query $query): array
    {
        $files = $this->finder->files()
            ->in($this->path);

        $contentRegex = $query->content();
        if ($contentRegex !== null) {
            $files->contains($contentRegex);
        }

        $result = [];
        foreach ($files as $file) {
            $note = $this->noteAt($file->getPathname());

            if ($note instanceof Asset) {
                continue;
            }

            $realMatches = [];

            if ($contentRegex !== null) {
                preg_match_all($contentRegex, $note->content, $matches);
                foreach (array_slice($matches, 1) as $groupIndex => $match) {
                    foreach ($match as $index => $x) {
                        $realMatches[$index][$groupIndex] = $x;
                    }
                }
            }

            $result[] = new MatchedNote($note, $realMatches);
        }

        return $result;
    }

    private function noteContent(Note $note): string
    {
        if ($note->frontMatter === []) {
            return $note->content;
        }

        $frontMatter = Yaml::dump($note->frontMatter, 1);

        return sprintf("---\n%s---\n%s", $frontMatter, $note->content);
    }

    private function noteAt(string $absolutePath): Note|Asset
    {
        $location = substr($absolutePath, strlen($this->path) + 1);
        $content  = file_get_contents($absolutePath);

        if (!str_ends_with($absolutePath, '.md')) {
            return new Asset($location, $content);
        }

        $parsed = $this->frontMatterParser->parse($content);

        return new Note($location, $parsed->frontMatter(), $parsed->content());
    }
}
