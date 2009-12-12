<?php

/*
* This file is part of the symfony Web Development Environment Installer.
* (c) Pablo Godel <pablo@servergrove.com>
* ServerGrove.com
*
* Use it as your own risk.
*/


if (!$this->askConfirmation('
Welcome to the Symfony Web Developent Environment installer!

This installer will perform the following steps:
- setup hosts file
- Add virtual host configuration to Apache web server
- Restart Apache

Keep in mind that you need to have permissions to modify the respective files and restart Apache.

You will be prompted to enter the IP address, hostname, and asked to confirm each operation. You can skip each step as desired.

Do you wish to continue on with the installation? (y/n)'))
{
  $this->logBlock('Symfony Web Developent Environment installation was cancelled!', 'ERROR_LARGE');
  return;
}


$projectName = $this->arguments['name'];
$documentRoot = sfConfig::get('sf_web_dir');

$defaultIp = '127.0.0.1';
$defaultHostname = $projectName.'.local';

$isWindows = false;

if ( isset( $_SERVER['SystemRoot'] ) )
{
  $isWindows = true;
  $winDir = $_SERVER['SystemRoot'];

  $this->logBlock("Windows OS detected at $winDir", 'INFO');
}

$ipAddress = $this->askAndValidate('What is the IP address for your web project (default: '.$defaultIp.')?'
, new sfValidatorString( array( 'empty_value' => $defaultIp, 'required' => false ) ), array('style' => 'QUESTION_LARGE'));

$this->logBlock("IP address set to $ipAddress", 'INFO');


$hostname = $this->askAndValidate('What is the hostname for your web project (default: '.$defaultHostname.')?'
, new sfValidatorString( array( 'empty_value' => $defaultHostname, 'required' => false ) ), array('style' => 'QUESTION_LARGE'));

$this->logBlock("Hostname set to $hostname", 'INFO');

$apacheConfFile = $this->askAndValidate('Enter the full file name of Apache Web Server configuration file (default: auto-search)?'
, new sfValidatorString( array( 'required' => false ) ), array('style' => 'QUESTION_LARGE'));

$this->logBlock( empty( $apacheConfFile ) ? "Will search for Apache configuration" : "Apache configuration set to $apacheConfFile", 'INFO');


if ( $isWindows )
{
  $hostsFile = $winDir.'\system32\drivers\etc\hosts';
}
else
{
  $hostsFile = '/etc/hosts';
}


if ( ! file_exists( $hostsFile ) )
{
  $hostsFile = $this->askAndValidate('Could not find hosts file. Please provide the full path name of the hosts file (press enter to skip this step)'
  , new sfValidatorFile( array( 'required' => false ) ), array('style' => 'QUESTION_LARGE'));
}
else
{
$this->logBlock("hosts file set to $hostsFile", 'INFO');
}

$content = "
# Added by Symfony Web Developent Environment installer
$ipAddress $hostname
";

if ( !empty( $hostsFile )
&& file_exists( $hostsFile )
&& $this->askConfirmation("Add '$ipAddress $hostname' to hosts file located at $hostsFile? (y/n)", 'QUESTION_LARGE'))
{
  if ( ! file_put_contents ( $hostsFile, $content, FILE_APPEND ) )
  {
    $this->logBlock("Failed to add $content to hosts file at $hostsFile. Please check that you have permissions to update this file.", 'ERROR_LARGE');
  }
  else
  {
      $this->logBlock("hosts file modified successfully.", 'INFO');
  }
}
else
{
  $this->logBlock("Skipped modifying hosts file.", 'INFO');
}

if ( empty( $apacheConfFile ) || ! file_exists( $apacheConfFile ) )
{
  $this->logBlock("Searching for Apache web server configuration file", 'INFO');

  if ( $isWindows )
  {

    $possibleLocations = array(
    'C:\Program Files\Zend\Apache2\conf\httpd.conf',
    );
  }
  else
  {
    $possibleLocations = array(
    '/etc/httpd/conf/httpd.conf',
    '/etc/apache2/conf/apache.conf',
    '/usr/local/apache2/conf/httpd.conf',
    '/usr/local/apache/conf/httpd.conf',
    '/usr/local/Zend/Apache2/conf/httpd.conf',
    );
  }

  foreach ($possibleLocations as $path )
  {
    if ( file_exists( $path ) )
    {
      $apacheConfFile = $path;
      $this->logBlock("Apache configuration found at $apacheConfFile", 'INFO');
      break;
    }
  }


}


if ( ! file_exists( $apacheConfFile ) )
{
  $hostsFile = $this->askAndValidate('Could not find hosts file. Please provide the full path name of the hosts file'
  , new sfValidatorFile(  array( 'required' => false ) ), array('style' => 'QUESTION_LARGE'));
}

$content = '
# Added by Symfony Web Developent Environment installer

<VirtualHost *:80>
    DocumentRoot "'.$documentRoot.'"
    ServerName '.$hostname.'
</VirtualHost>

<Directory "'.$documentRoot.'">
  AllowOverride All
</Directory>

';

if ( !empty( $apacheConfFile )
&& file_exists( $apacheConfFile )
&& $this->askConfirmation("Add Virtual Host section to $apacheConfFile? (y/n)", 'QUESTION_LARGE'))
{
  if ( ! file_put_contents ( $apacheConfFile, $content, FILE_APPEND ) )
  {
    $this->logBlock("Failed to add configuration to $apacheConfFile. Please check that you have permissions to update this file.", 'ERROR_LARGE');
  }
  else
  {
      $this->logBlock("Apache configured successfully.", 'INFO');
  }

}

if ($this->askConfirmation("Do you want to restart Apache to load the new configuration? (y/n)", 'QUESTION_LARGE'))
{
  if ( $isWindows )
  {
    $cmd = "";
  }
  else
  {
    $cmd = "service httpd restart";
  }

  if ( empty( $cmd ) )
  {
    $this->logBlock("Could not find a way to restart Apache. Please restart it manually.", 'ERROR_LARGE');
  }
  else
  {
    if ( ! passthru( $cmd ) )
    {
      $this->logBlock("Failed to restart Apache. Please check that you have permissions to restart Apache.", 'ERROR_LARGE');
      return;
    }
  }

}

$this->logBlock("Symfony Web Development environemnt all setup. Go to http://$hostname/", 'INFO');
