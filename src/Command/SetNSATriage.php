<?php
namespace freshdeskhelper\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use freshdeskhelper\Helper;

class SetNSATriage extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'set-nsa-triage';

    protected function configure()
    {
        // '--dry-run',InputOption::VALUE_NONE,'run without making changes'
        $this->addOption(
            'dry-run',
            'd'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // ...
        Helper::setTriageNSA($input->getOption('dry-run'));
    }
}

