<?php

namespace App\Console\Helper;

use Symfony\Component\Finder\Finder;

final class FinderTreeLoader
{
    /**
     * Load directory items at the given path (depth == 0) into a tree structure.
     *
     * @param string     $path   The filesystem path to load
     * @param array|null $parent Reference to the parent node array
     *
     * @return array List of node arrays with keys: name, path, open, children, parent
     */
    public function load(string $path, ?array &$parent = null): array
    {
        $nodes = [];
        $finder = (new Finder())
            ->in($path)
            ->depth('== 0')
            ->filter(fn ($file) => !\in_array($file->getFilename(), ['vendor', '.git'], true))
            ->sortByName();

        foreach ($finder as $file) {
            $nodes[] = $node = [
                'name' => $file->getFilename(),
                'path' => $file->getRealPath(),
                'open' => false,
                'children' => [],
                'parent' => &$parent,
            ];
        }

        // Reassign parent reference for each node
        foreach ($nodes as &$node) {
            $node['parent'] = &$parent;
        }

        return $nodes;
    }
}
