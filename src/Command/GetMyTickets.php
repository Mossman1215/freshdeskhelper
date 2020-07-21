<?php
namespace freshdeskhelper\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use freshdeskhelper\Helper;

class GetMyTickets extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'getmytickets';

    protected function configure()
    {
      $this->addOption('json');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // ...
        $tickets = Helper::getMyTickets();
        if($input->getOption('json')){
         $output->writeln(\json_encode($tickets));
        }else{
          foreach ($tickets as $id => $ticket) {
            $output->writeln($ticket['subject']);
          }
        }
    }
}

