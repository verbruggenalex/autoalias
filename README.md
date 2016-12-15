# autoalias
A composer package that enables auto alias generation in composer
projects.

## Installation
It is recommended that you make the installation in your global composer
project. Currently there is no usage for multiple instances.

### Get the package
First off fetch the package. Currently there is no stable version, so 
you will just have the latest master. There are still todo's before I
can release the first version.
```
$ composer global require "verbruggenalex/autoalias:dev-master"
```

### Install the package
Execute the install script from the package. This assumes your home 
directory is the location of your global composer install. So adjust
your path accordingly.
```
$ cd ~/.composer/vendor/verbruggenalex/autoalias
$ composer run-script post-install-cmd
```

The result of the installation should look something like this:
```
> Autoalias\Component\Console\Installer\Installer::postInstall
 ------------------------------------------------------------------------------
 // ~/.autoalias_aliases: file created.
 // ~/.bashrc: autoalias succesfully added.
 !! To complete installation refresh your .bashrc file by executing: . ~/.bashrc
 ------------------------------------------------------------------------------
```
The important thing is the post installation script. It needs to:
- create an **.autoalias_aliases** file in your home directory.
- append the autoalias-execute and .autoalias_aliases file inclusion
in the **.bashrc** file of your home directory.
 
This install script will not source your .bashrc file afterwards. So to
complete the install process you need to execute `. ~/.bashrc` yourself.
 
Carefully check the message and/or verify that you have the needed
file and code. Your .bashrc file should have the following appended:
```bash
# ================================================================================
# Autoalias function execution. Do not alter.
  AUTOALIAS_ROOT=%ROOT_INSTALL_PATH%
  function autoalias-execute() {
      # Set command and params.
      command=${@:1:1}
      params=${@:2}
      # Request return variables.
      declare -A return=$(php $AUTOALIAS_ROOT/autoalias autoalias:execute --command="${command}" --params="${params// \ }")
      # Output message.
      if [ "${return[message]}" != "" ]; then
          echo ${return[message]}
      fi
      # Execute command.
      eval ${return[command]}
      # Refresh autoaliases if needed.
      if [ "${return[refresh]}" != "false" ]; then
          php $AUTOALIAS_ROOT/autoalias autoalias:refresh --composer-json="${return[refresh]}"
          . ~/.autoalias_aliases
      fi
  }
  # Include our autoalias_aliases.
  if [ -f ~/.autoalias_aliases ]; then
      . ~/.autoalias_aliases
  fi
# ================================================================================
 ```
Where **%ROOT_INSTALL_PATH%** will be replaced with the location of where
you installed the package. **So moving it will break the functionality!**

When first installed the included .autoalias_aliases file will be copied
to your home directory. This file contains one preset:
```bash
alias composer='autoalias-execute composer'
```
**Note:** When executing `composer install` or `composer update` from
within a composer project it will add any aliases from its bin folder
that are not present yet in ~/.autoalias_aliases.
 
## Usage
If all went well you should now receive a message when using one of
these aliases in a composer project:
```
$ drush status
Executing local /var/www/your-project/vendor/drush/drush/drush
 PHP executable         :  /usr/bin/php
 PHP configuration      :  /etc/php/7.0/cli/php.ini
 PHP OS                 :  Linux
 Drush script           :  /var/www/your-project/vendor/drush/drush/drush.php
 Drush version          :  8.1.5
 Drush temp directory   :  /tmp
 Drush configuration    :
 Drush alias files      :
```

**Note:** If running your command has no output it might be you need to
source the .bashrc yourself, for that you can use:
```
$ . ~/.bashrc
```

## Uninstall
To help you uninstall we have provided a pre-uninstall script that you
can manually perform by executing the following command.
```
$ autoalias-execute autoalias-uninstall
```

This script will:
- unalias all the registered aliases.
- remove the ~/.autoalias_aliases file.
- remove the autoalias code from your ~/.bashrc.

After that you can remove the source code by executing:
```
$ composer global remove "verbruggenalex/autoalias"
```