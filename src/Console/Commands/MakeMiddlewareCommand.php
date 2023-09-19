<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeMiddlewareCommand extends Command
{
    protected function configure()
    {
        $this->setName('make:middleware');
        $this->addArgument('name', InputArgument::REQUIRED, 'Middleware name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $middleware = $input->getArgument('name');
        $content = "<?php

namespace App\Middleware;

use Symfony\Component\HttpFoundation\Request;

class $middleware extends Middleware
{
    public function next(Request \$request, Middleware \$next)
    {
        
        
        return \$next(\$request);
    }
}";

        file_put_contents(__DIR__ . "/../../Middleware/$middleware.php", $content);
        return Command::SUCCESS;
    }
}