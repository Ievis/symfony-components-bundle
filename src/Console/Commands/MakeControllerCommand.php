<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeControllerCommand extends Command
{
    protected function configure()
    {
        $this->setName('make:controller');
        $this->addArgument('name', InputArgument::REQUIRED, 'Controller name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $controller = $input->getArgument('name');
        $content = "<?php
        
namespace App\Controller;

class $controller extends AbstractController
{

}";

        file_put_contents(__DIR__ . "/../../Controller/$controller.php", $content);
        return Command::SUCCESS;
    }
}