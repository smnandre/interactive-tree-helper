<?php

namespace App\Console;

final class InputHandler
{
    public function handle(
        string $key,
        int &$selectedIndex,
        array &$visibleNodes,
        callable $onExpand,
        callable $onCollapse,
    ): void {
        switch ($key) {
            case "\033[A":
                $selectedIndex = max(0, $selectedIndex - 1);
                break;

            case "\033[B":
                $selectedIndex = min(\count($visibleNodes) - 1, $selectedIndex + 1);
                break;

            case "\033[C":
                $node = &$visibleNodes[$selectedIndex];
                if (is_dir($node['path']) && !$node['open']) {
                    $onExpand($node, $selectedIndex);
                }
                break;

            case "\033[D":
                $node = &$visibleNodes[$selectedIndex];
                if (!empty($node['open'])) {
                    $onCollapse($node, $selectedIndex);
                } else {
                    $onCollapse($node, $selectedIndex, true);
                }
                break;

            case 'q':
            case "\033":
                exit(0);
        }
    }
}
