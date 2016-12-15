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

    // Check if we have an executable of the requested alias in our bin folder.
    if (is_file($composer_json)) {
      $project_path = dirname($composer_json);
      $json = file_get_contents($composer_json);
      $composer = json_decode($json);

      if (!empty($composer->config->{'bin-dir'}) && file_exists($project_path . '/' . $composer->config->{'bin-dir'})) {
        $bin_dir = $project_path . '/' . rtrim($composer->config->{'bin-dir'}, '/');
        $executable = realpath($bin_dir . '/' . $command);
        if (file_exists($executable)) {
          // @todo: allow for custom params on global and/or project level.
          $commandline = $executable . ' ' . $params;
          $messages[] = '<comment>Executing local <info>' . $executable . '</info></comment>';
        }
        else {
          $global_command = exec('which ' . $command);
          if (!empty($global_command)) {
            $commandline = $global_command . ' ' . $params;
            $messages[] = '<comment>Executing global <info>' . $global_command . '</info></comment>';
          }
          else {
            // @TODO
          }
        }

        // Request autoalias refresh after command is executed.
        if ($command == 'composer' && in_array(strtok($params, ' '), array('install', 'update'))) {
          $refresh = $composer_json;
        }
      }
    }
    else {
      $global_command = exec('which ' . $command);
      if (!empty($global_command)) {
        $commandline = $global_command . ' ' . $params;
        $messages[] = '<comment>Executing global <info>' . $global_command . '</info></comment>';
      }
      else {
        // @TODO
      }
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
