<?php
namespace freshdeskhelper\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use freshdeskhelper\Helper;

class UpdateNSA extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'update-nsa';

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // ...
        Helper::updateAllNSADates();
    }
}

