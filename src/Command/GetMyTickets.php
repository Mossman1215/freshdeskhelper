<?php
namespace freshdeskhelper\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use freshdeskhelper\Helper;
use Symfony\Component\Console\Helper\Table;

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
      $tickets = Helper::getMyTickets();
      if($input->getOption('json')){
        $output->writeln(\json_encode($tickets));
      }else{
        $table = new Table($output);
        $table
            ->setHeaders(['ID', 'Subject','URL']);
        foreach ($tickets as $id => $ticket) {
          $table ->addRow([$ticket['id'],$ticket['subject'],'https://silverstripe.freshservice.com/a/tickets/'.$ticket['id']]);
        }
        $table->render();
      }
    }
}

