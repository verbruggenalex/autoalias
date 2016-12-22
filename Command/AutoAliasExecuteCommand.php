<?php

namespace Autoalias\Component\Console\Command;

use Autoalias\Component\Console\Helper\AutoAliasComposerHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AutoAliasExecuteCommand extends Command
{
  protected function configure()
  {
    // Setup command.
    $this->setName('autoalias:execute');
    $this->setDescription('Execute autoalias.');
    $this->addOption('command', null, InputOption::VALUE_REQUIRED, 'The command to execute.');
    $this->addOption('params', null, InputOption::VALUE_OPTIONAL, 'The parameters for the command.');
    $this->addOption('refresh', null, InputOption::VALUE_OPTIONAL, 'Whether or not to refresh the aliases.');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Set parameters.
    $composer_helper = new AutoAliasComposerHelper($input, $output);
    $composer_json = $composer_helper->findComposerFile();
    $command = $input->getOption('command');
    $params = !empty($input->getOption('params')) ? str_replace('"', '', $input->getOption('params')) : '';
    $refresh = !empty($input->getOption('refresh')) ? $input->getOption('refresh') : 'false';
    $commandline = '';
    $messages = array();
    $output->setFormatter(new OutputFormatter(true));
    $executable = $composer_helper->retrieveCommand($command);

    if (!empty($executable)) {
      // @todo: allow for custom params on global and/or project level.
      $commandline = $executable . ' ' . $params;
      $messages[] = '<comment>Executing: <info>' . $executable . '</info></comment>';
    }

    // Request autoalias refresh after command is executed.
    if ($command == 'composer' && in_array(strtok($params, ' '), array('install', 'update'))) {
      $refresh = $composer_json;
    }
    // Uninstall command: unaliases and execute uninstall script.
    if ($command == 'autoalias-uninstall') {
      $home = exec('echo ~');
      $autoalias_aliases = $home . '/.autoalias_aliases';
      if ($aliases = file_get_contents($autoalias_aliases)) {
        preg_match_all('/alias (.*?)=.*?/s', $aliases, $matches);
        if (!empty($matches[1])) {
          $unalias_command =  'unalias ' . implode(' ', $matches[1]) . ';';
        }
      }
      $uninstall_command = 'php -r \'include \\"' . $home . '/.composer/vendor/autoload.php\\"; \Autoalias\Component\Console\Installer\Installer::preUninstall();\'';
      $commandline = $uninstall_command . ' && ' . $unalias_command;
    }

    // Send variables in associative array.
    $variables = array(
      OutputFormatter::escape('[message]="') . implode('\n', $messages) . '"',
      OutputFormatter::escape('[refresh]="') . $refresh . '"',
      OutputFormatter::escape('[command]="') . $commandline . '"',
    );

    $output->write('( ' . implode(' ', $variables) . ' )');
  }
}
