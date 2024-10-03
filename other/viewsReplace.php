<?php

$pathCmd = "";
// Drupal 7 ID
$drupal7="21e1fada-199c-492b-97bd-0b36b53a9da0";
// Tribute Media D7 Base ID
$d7Base="2e592dbc-b5a2-4bd9-972e-1436be44b07c";

// Get site name
print "What site do you need to replace views in?\n";
$site = readline(":");
print "\n";

// Set $viewsPath and $sftpCmd
$currentPath = getcwd();
$viewsPath = "cd ~/scripts/github/tm-d7-base/profiles/tribute_media/modules/contrib/views/includes";
$sftpCmd = shell_exec('terminus connection:info '.$site.'.updates --fields="sftp_command" --format="string"');

// Determining the site upstream ID
$upstreamType = shell_exec('terminus site:info '.$site.' --fields=upstream --format="csv"');
$upstreamType = explode("\n", $upstreamType);
$upstreamType = $upstreamType[1];
$upstreamType = explode("\"", $upstreamType);
$upstreamType = $upstreamType[1];
$upstreamType = explode(":", $upstreamType);

// Setting $upstreamType to proper site ID
if ($upstreamType[0] == $drupal7) {
  $pathCmd = "cd code/sites/all/modules/contrib/views/includes";
} elseif ($upstreamType[0] == $d7Base) {
  $pathCmd = "cd code/profiles/tribute_media/modules/contrib/views/includes";
}

// Print out commands
print $viewsPath."\n";
print $sftpCmd."\n";
print $pathCmd."\n\n";
print "put handlers.inc\n\nexit\n\n";
print "cd ".$currentPath."\n";
?>
