<?php
require_once __DIR__."/../functions/headerFunctions.php";
require_once __DIR__."/../functions/macroFunctions.php";

function continueWithScript($startTime = 0, $isError = False) {
  $fd = fopen('php://stdin', 'r');

  // prepare arguments for stream_select()
  $read = array($fd);
  $write = $except = array(); // we don't care about this
  if ($startTime == 0) {
    $timeout = 10;
    $startTime = time();
  } else {
    if ($startTime - time() + 10 > 0) {
      $timeout = $startTime - time() + 10;
    }
  }

  // wait for maximal 5 seconds for input
  print "Do you want to build siteList now [strongly recommended]? [y] or [n]\n";
  if(stream_select($read, $write, $except, $timeout)) {
    $response = fgets($fd)."\n";
    if ($response == "y\n\n") {
      updateSiteList();
    } elseif ($response == "n\n\n") {
      return;
    } else {
      if ($isError == True) {
        print "\e[1A\e[K";
      }
      print "\e[1A\e[K\e[1A\e[K";
      print "Please enter a valid input.\n";
      return continueWithScript($startTime, True);
    }
  } else {
    updateSiteList();
  }
  fclose($fd);
}

headers(array(array(
  "Initializing Repository File Structure"
)), array(
  array(1,0)
));

if (shell_exec("ls -l ".__DIR__."/../functions/ | wc -l") !== "10\n" &&
shell_exec("ls -l ".__DIR__."/../siteInfo/ | wc -l") !== "8\n") {

  // Create functions/functionFiles
  print " ".color("[notice]", "white", "cyan")." Creating functions/functionFiles/\n";
  shell_exec("mkdir ".__DIR__."/../functions/functionFiles");
  usleep(250000);
  print "\e[1A\e[K ".color("[notice]", "white", "cyan")." Created functions/functionFiles/\n";

  // Create siteInfo/listArchive
  print " ".color("[notice]", "white", "cyan")." Creating siteInfo/listArchive\n";
  shell_exec("mkdir ".__DIR__."/../siteInfo/listArchive");
  usleep(250000);
  print "\e[1A\e[K ".color("[notice]", "white", "cyan")." Created siteInfo/listArchive/\n";

  // Create siteInfo/siteList.txt
  print " ".color("[notice]", "white", "cyan")." Creating siteInfo/siteList.txt\n";
  shell_exec("touch ".__DIR__."/../siteInfo/siteList.txt");
  usleep(250000);
  print "\e[1A\e[K ".color("[notice]", "white", "cyan")." Created siteInfo/siteList.txt\n";

  // Create siteInfo/sitesToFinish.txt
  print " ".color("[notice]", "white", "cyan")." Creating siteInfo/sitesToFinish.txt\n";
  shell_exec("touch ".__DIR__."/../siteInfo/sitesToFinish.txt");
  usleep(250000);
  print "\e[1A\e[K ".color("[notice]", "white", "cyan")." Created siteInfo/sitesToFinish.txt\n\n";

  continueWithScript();
} else {
  print "Respository Already Initialized\n";
}

 ?>
