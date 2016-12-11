<?php

namespace Autoalias\Component\Console\Helper;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AutoAliasComposerHelper
{
  /**
   * PhingPropertiesHelper constructor.
   *
   * Setup our input output interfaces.
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  function __construct(InputInterface $input, OutputInterface $output)
  {
    $this->input = $input;
    $this->output = $output;
  }

  /**
   * Returns absolute path to build.xml if found.
   *
   * This function takes the current working directory and searches upwards until the
   * file gets found. When the path remains the same it means we have reached the
   * root of the filesystem and we return false.
   *
   * @param string $path
   *   This is always set with getcwd(). But because of the recursiveness of the
   *   function we can not enter it in the function itself (I think).
   * @return bool|string
   *   False if we reached root without finding it. Absolute path if found.
   */
  public function findComposerFile($path = '')
  {
    $path = !empty($path) ? $path : getcwd();
    $filename = 'composer.json';
    $filepath = $path . '/' . $filename;
    // If the current folder does not contain the build file, proceed.
    if (!is_file($filepath)) {
      // If we haven't reached root yet, retry in parent folder.
      if (dirname($path) != $path) {
        return $this->findComposerFile(dirname($path));
      }
      else {
        return FALSE;
      }
    }
    // If found return absolute path.
    else {
      return $filepath;
    }
  }
}
