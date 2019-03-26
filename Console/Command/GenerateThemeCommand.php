<?php
namespace Angeldm\CopyThemeOverride\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateThemeCommand extends Command
{
    public function configure()
    {
        $this->setName('dev:theme:generate');
        $this->setDescription('Generates a base theme to get you started with themes');
        $this->addArgument('vendor', InputArgument::REQUIRED, "The name of the theme vendor");
        $this->addArgument('name', InputArgument::REQUIRED, "The name of the theme");
    }
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Theme "$this->getArgument('vendor')/$this->addArgument('name')" created</info>');
    }
}
