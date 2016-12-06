<?php

namespace Autoalias\Component\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AutoAliasMessageCommand extends Command
{
  protected function configure()
  {
    // Setup command.
    $this->setName('autoalias:message');
    $this->setDescription('Autoalias message.');
    $this->addOption('command', null, InputOption::VALUE_OPTIONAL, 'The command to execute.');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Get the command
    // @todo: make message dynamic depending command type.
    $command = $input->getOption('command');

    if (substr($command, 0, 1) === "/") {
      // Write the message.
      // @todo: make the lines fit the longest text.
      $message = array(
        '<comment> ------------------------------------------------------------------------------',
        ' // Autoalias in use: <info>' . $command . '</info>',
        ' // If you wish to change these settings use the command "autoalias configure".',
        ' ------------------------------------------------------------------------------</comment>'
      );
      $output->writeln(implode(PHP_EOL, $message));
    }
  }
}
