<?php

namespace Autoalias\Component\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ChangeDirectoryCommand extends Command
{

  protected function configure()
  {
    // Setup command.
    $this->setName('cd');
    $this->setDescription('Change directory replacement for cd command.');
    $this->addArgument('origin', InputArgument::REQUIRED);
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Set parameters.
    $origin = realpath($input->getArgument('origin'));
    $destination = realpath(getcwd());
    $home = exec('echo ~');
    $index = __DIR__ . '/../index.yml';
    $composer_aliases = $home . '/.composer_aliases';
    $composer_json = $destination . '/composer.json';

    // Update our composer projects index.
    $this->updateIndex($destination, $index);

    $aliases = array();
    $yaml = Yaml::parse(file_get_contents(__DIR__ . '/../index.yml'));
    $variables = $this->generateVariablesForAliasGeneration($origin, $destination, $yaml);
    list($destination_root, $destination_bin_dir, $origin_root, $origin_bin_dir) = $variables;

    // If our destination belongs to an indexed project.
    if ($destination_root
      && $origin != $destination
      && file_exists($destination)
      && file_exists($destination_bin_dir)
    ) {
      // If we did not come from the same project, create our aliases.
      if ($destination_root != $origin_root) {
        // Start with unsetting previous aliases if needed.
        if ($origin_bin_dir) {
          $this->addProjectAliases('unset', $origin_bin_dir, $aliases);
        }
        // If our destination project is not excluded create the new aliases.
        if (!isset($yaml['exclude'][$destination_root]) && $destination_bin_dir) {
          $this->addProjectAliases('set', $destination_bin_dir, $aliases);
          $output->writeln('<error>Entered project: aliases in effect.</error>', OutputInterface::VERBOSITY_VERBOSE);
        }
        // If it is exluded, notify the user.
        else {
          $output->writeln('<error>Project excluded: no aliases in effect.</error>', OutputInterface::VERBOSITY_VERBOSE);
        }

        // Write the alias commands to the file.
        file_put_contents($composer_aliases, implode(PHP_EOL, $aliases));
      }
      // If we are not in a project and don't have to unset any aliases.
      else {
        // Remove any content from the composer aliases file.
        $this->clearFile($composer_aliases);
      }
    }
    // Else if we come from a project but are not currently in one. Unset the
    // aliases of previous project.
    elseif ($origin_root && $origin_bin_dir != $destination_bin_dir) {
      $this->addProjectAliases('unset', $origin_bin_dir, $aliases);
      file_put_contents($composer_aliases, implode(PHP_EOL, $aliases));
      $output->writeln('<error>Exited project: no aliases in effect.</error>', OutputInterface::VERBOSITY_VERBOSE);
    } // If we are not in a project and don't have to unset any aliases.
    else {
      // Remove any content from the composer aliases file.
      $this->clearFile($composer_aliases);
    }
  }

  /**
   * Helper function to generate variables needed for the alias generation logic.
   *
   * @param $origin
   *   The path we came from.
   * @param $destination
   *   The path we are currently in.
   * @param $yaml
   *   The index file.
   *
   * @return array
   *   An array consisting of 4 needed variables.
   */
  private function generateVariablesForAliasGeneration($origin, $destination, $yaml) {
    // Setup all variables.
    $includes = isset($yaml['include']) ? array_keys($yaml['include']) : array();
    $includes_escaped = array_map(function ($elem) { return preg_quote($elem, '~'); }, $includes);
    preg_match('~' . implode('|', $includes_escaped) . '~', $destination, $destination_project);
    preg_match('~' . implode('|', $includes_escaped) . '~', $origin, $origin_project);
    $destination_root = !empty($destination_project[0]) ? $destination_project[0] : '';
    $origin_root = !empty($origin_project[0]) ? $origin_project[0] : '';
    $destination_bin_dir = isset($yaml['include'][$destination_root]['bin-dir']) ? $destination_root . '/' . $yaml['include'][$destination_root]['bin-dir'] : FALSE;
    $origin_bin_dir = isset($yaml['include'][$origin_root]['bin-dir']) ? $origin_root . '/' . $yaml['include'][$origin_root]['bin-dir'] : FALSE;

    // Return needed variables.
    return array(
      $destination_root,
      $destination_bin_dir,
      $origin_root,
      $origin_bin_dir
    );
  }

  /**
   * Helper function to update alias commands for a project.
   *
   * @param string $type
   *   Type must be set to 'set' or 'unset' to generate the correct alias commands.
   * @param string $project_bin_dir
   *   The path to the project bin folder.
   * @param array $aliases
   *   An array of alias commands to add more alias commands to.
   */
  private function addProjectAliases($type, $project_bin_dir, &$aliases)
  {
    $files = file_exists($project_bin_dir) ? array_diff(scandir($project_bin_dir), array('..', '.')) : array();
    foreach ($files as $filename) {
      $path = realpath($project_bin_dir . '/' . $filename);
      if ($type == 'unset') {
        $aliases[] = "unalias $filename";
      }
      if ($type == 'set') {
        switch ($filename) {
          case "phing":
            $options = " -find build.xml";
            break;
          default:
            $options = "";
        }

        $aliases[] = "alias $filename=\"$path$options\"";
      }
    }
  }

  /**
   * Helper function to clear file from content.
   *
   * @param string $file
   *   Path to file to clear from content.
   */
  private function clearFile($file)
  {
    // If there is content in our aliases file, clear it.
    if (file_exists($file) && filesize($file) != 0) {
      $fp = fopen($file, "r+");
      ftruncate($fp, 0);
      fclose($fp);
    }
  }

  /**
   * Helper function to update our index.
   *
   * @param string $destination
   *   Our current path to check for composer project.
   * @param string $index
   *   Path to our index file that holds all registered composer projects.
   */
  private function updateIndex($destination, $index) {
    $composer_json = $destination . '/composer.json';
    $yaml = Yaml::parse(file_get_contents($index));
    // Add composer project root to index, or update it.
    if (file_exists($composer_json)) {
      $json = file_get_contents($composer_json);
      $composer = json_decode($json);

      if (!empty($composer->config->{'bin-dir'}) && file_exists($composer->config->{'bin-dir'})) {
        $bin_dir = rtrim($composer->config->{'bin-dir'}, '/');
        $yaml['include'][$destination]['bin-dir'] = $bin_dir;
        $updated_yaml = Yaml::dump($yaml, 5);
        file_put_contents($index, $updated_yaml);
      }
    }
  }
}