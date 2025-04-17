<?php

namespace App\Console;

use Symfony\Component\Console\Output\OutputInterface;

final class TreeRenderer
{
    public function render(
        OutputInterface $output,
        array &$tree,
        int $selectedIndex,
        array &$visibleNodes,
        string $rootPath,
        string $bottomMessage = '',
    ): void {
        $lines = [];
        $visibleNodes = [];
        $this->collectTree($tree, '', $lines, $visibleNodes, $selectedIndex);

        $totalRows = (int) trim(shell_exec('tput lines')) ?: 24;
        // Reserve 1 row for breadcrumb and 1 row for the bottom message
        $treeRows = max(1, $totalRows - 2);
        $start = max(0, min($selectedIndex - (int) ($treeRows / 2), \count($lines) - $treeRows));

        // Clear screen and move cursor to home
        $output->write("\033[2J\033[H");

        // Render Breadcrumb
        $selectedNode = $visibleNodes[$selectedIndex] ?? null;
        $relativePath = '';
        if ($selectedNode) {
            $relativePath = ltrim(str_replace(rtrim($rootPath, \DIRECTORY_SEPARATOR), '', $selectedNode['path']), \DIRECTORY_SEPARATOR);
        }
        // Ensure breadcrumb doesn't wrap and clear rest of line
        $breadcrumbLine = \sprintf('<breadcrumb>Path: %s</breadcrumb>', $relativePath);
        $output->writeln($breadcrumbLine."\033[K"); // \033[K clears line from cursor to end

        // Render Tree Lines
        $endLine = min($start + $treeRows, \count($lines));
        for ($i = $start; $i < $endLine; ++$i) {
            // Ensure tree lines don't wrap and clear rest of line
            $output->writeln($lines[$i]."\033[K");
        }

        if (!empty($bottomMessage)) {
            // Move cursor to the last row, first column
            $output->write("\033[{$totalRows};1H");
            // Write the message and clear the rest of the line
            $output->write($bottomMessage."\033[K");
        }
    }

    private function collectTree(
        array &$nodes,
        string $prefix,
        array &$lines,
        array &$visibleNodes,
        int $selectedIndex,
    ): void {
        foreach ($nodes as &$node) {
            $index = \count($visibleNodes);
            $visibleNodes[] = &$node;

            $pointer = ($index === $selectedIndex) ? '➜' : ' ';
            $hasChildren = is_dir($node['path']);
            $branch = $hasChildren ? ($node['open'] ? '▼' : '▶') : '•';

            if ($index === $selectedIndex) {
                $name = \sprintf('<highlight>%s</highlight>', $node['name']);
            } elseif ($hasChildren) {
                $name = \sprintf('<dir>%s</dir>', $node['name']);
            } else {
                $name = \sprintf('<file>%s</file>', $node['name']);
            }

            $lines[] = \sprintf('%s %s %s %s', $pointer, $prefix, $branch, $name);

            if ($hasChildren && $node['open']) {
                $this->collectTree($node['children'], $prefix.'  ', $lines, $visibleNodes, $selectedIndex);
            }
        }
    }
}
