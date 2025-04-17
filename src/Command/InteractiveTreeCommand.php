<?php

namespace App\Command;

use App\Console\Helper\FinderTreeLoader;
use App\Console\InputHandler;
use App\Console\TreeRenderer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:tree', description: 'Interactive tree browser for the project')]
class InteractiveTreeCommand extends Command
{
    private array $tree = [];

    private array $visibleNodes = [];

    private int $selectedIndex = 0;

    private string $rootPath;

    public function __construct(
        private readonly InputHandler $inputHandler,
        private readonly FinderTreeLoader $treeLoader,
        private readonly TreeRenderer $treeRenderer,
        #[Autowire('%kernel.project_dir%')] string $projectDir,
    ) {
        parent::__construct();
        $this->rootPath = $projectDir;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $formatter = $output->getFormatter();

        $output->setDecorated(true);
        $formatter->setDecorated(true);

        $formatter->setStyle('highlight', new OutputFormatterStyle('bright-magenta'));
        $formatter->setStyle('dir', new OutputFormatterStyle('bright-cyan', '', ['bold']));
        $formatter->setStyle('file', new OutputFormatterStyle('default'));
        $formatter->setStyle('breadcrumb', new OutputFormatterStyle('blue', '', ['bold']));
        $formatter->setStyle('bottom-info', new OutputFormatterStyle('yellow'));

        $this->tree = $this->treeLoader->load($this->rootPath);

        system('stty -icanon -echo');
        stream_set_blocking(\STDIN, false);
        $output->write('[?25l'); // Hide cursor

        $bottomMessage = '<bottom-info>Use Arrows (↑↓←→) | Enter/Space (toggle) | Ctrl+C/Enter (quit)</bottom-info>';

        $this->treeRenderer->render(
            $output,
            $this->tree,
            $this->selectedIndex,
            $this->visibleNodes,
            $this->rootPath,
            $bottomMessage
        );

        while (true) {
            $char = fread(\STDIN, 1);
            if ('' === $char || false === $char) {
                usleep(10000);
                continue;
            }
            if ('' === $char || '
' === $char) {
                break;
            }
            $key = '' === $char
                ? $char.fread(\STDIN, 2)
                : $char;

            $this->inputHandler->handle(
                $key,
                $this->selectedIndex,
                $this->visibleNodes,
                fn (array &$node) => $this->expandNode($node),
                fn (array &$node, int &$i, bool $toParent = false) => $this->collapseNode($node, $i, $toParent)
            );

            $this->treeRenderer->render(
                $output,
                $this->tree,
                $this->selectedIndex,
                $this->visibleNodes,
                $this->rootPath,
                $bottomMessage
            );
        }

        $output->write('[?25h');
        system('stty sane');

        return Command::SUCCESS;
    }

    private function expandNode(array &$node): void
    {
        $node['open'] = true;
        $node['children'] = $this->treeLoader->load($node['path'], $node);
    }

    private function collapseNode(array &$node, int &$selectedIndex, bool $toParent = false): void
    {
        if ($node['open'] && !$toParent) {
            $node['open'] = false;

            return;
        }
        if (isset($node['parent'])) {
            $parentIndex = array_search($node['parent'], $this->visibleNodes, true);
            if (false !== $parentIndex) {
                $selectedIndex = $parentIndex;
            }
        }
    }
}
