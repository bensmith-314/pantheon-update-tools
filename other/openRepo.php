<?php
/** Declare what the url to the remote repository is and determine what the OS
 * running the script is.
 */
$repoURL = "https://github.com/bensmith314/pantheon-update-tools";
$unameOut = shell_exec("uname -s");

/** Depending on what OS is running this script, $repoURL will be opened with by
 * the proper means whether the OS is Linux or MacOS (Darwin). If the script is
 * for whatever reason not being run on MacOS or Linux it will print out
 * $repoURL.
 */
if ($unameOut == "Linux\n") {
  $open = shell_exec("xdg-open $repoURL");
} elseif ($unameOut == "Darwin\n") {
  shell_exec("open -n \"$repoURL\"");
} else {
  print "$repoURL\n";
}
 ?>
