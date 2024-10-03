<?php

require_once __DIR__ . "/utilityFunctions.php";

//login

// Checks to see if the site given exists, returns true and false
function siteDoesExist($site) {
  $siteList = shell_exec("terminus site:list --name=$site --format=csv --fields=name --ansi");
  $siteList = explode("\n", $siteList);
  if ($siteList[1] === $site) {
    return True;
  } elseif (count($siteList) > 1) {
    for ($i=0; $i < count($siteList); $i++) {
      if ($siteList[$i] === $site) {
        return True;
      }
    }
    // Removes output message that isn't needed
    print "\e[1A\e[K";
    return False;
  } else {
    return False;
  }
}

/** Checks that the given site and environment do exist. Can create environment
 * if necessary / specified to do so.
 */
function envDoesExist($site, $env, $create = 1) {
  if (siteDoesExist($site)) {
    $envList = shell_exec("terminus multidev:list $site --format=csv --fields=id");
    $envList = explode("\n", $envList);
    for ($i=1; $i < (count($envList) - 1); $i++) {
      if ($envList[$i] === $env) {
        print " ". color("[notice]", "white", "cyan")." Multidev environment \"$env\" exists\n";
        return True;
      }
    }
    if ($create == 1) {
      shell_exec("terminus multidev:create $site.live $env --ansi");
      return True;
    } else {
      print " ". color("[error]", "white", "red")." Multidev environment \"$env\" doesn't exist\n";
      return False;
    }

  } else {
    print " ". color("[error]", "white", "red")." Site \"$site\" doesn't exist\n";
    return False;
  }
}

/** Verifies whether or not a site can be updated or have work done on it based
 * on a few different criteria.
 */
function canWork($site, $errors = 1) {
  $upstreams = array(
    "drupal6" => "974b75c2-4ba7-49f8-8a54-3a45c07dfe02",
    "drupal7" => "21e1fada-199c-492b-97bd-0b36b53a9da0",
    "tmd7" => "2e592dbc-b5a2-4bd9-972e-1436be44b07c",
    "drupal8" => "8a129104-9d37-4082-aaf8-e6f31154644e"
  );

  $siteInfo = shell_exec("terminus site:info $site --fields=plan_name,upstream,frozen --format=csv");
  $siteInfo = explode("\n", $siteInfo);
  $siteInfo = explode(",", $siteInfo[1]);
  $plan = $siteInfo[0];
  $upstream = $siteInfo[1];
  $upstream = substr($upstream, 1, 36);
  $status = $siteInfo[2];

  // Site isn't frozen
  if ($status == 'true') {
    if ($errors == 1) {
      print " ".color("[error]", "white", "red")." Site \"$site\" is frozen\n";
    }
    return False;
  }
  // Site isn't sandboxed
  if ($plan == "Sandbox") {
    if ($errors == 1) {
      print " ".color("[error]", "white", "red")." Site \"$site\" is Sandboxed\n";
    }
    return False;
  }
  // Site has proper upstream (d7, d8, or tmd7)
  if ($upstream !== $upstreams["drupal7"] && $upstream !== $upstreams["drupal8"] && $upstream !== $upstreams["tmd7"]) {
    if ($errors == 1) {
      print " ".color("[error]", "white", "red")." Site \"$site\" has wrong upstream\n";
    }
    return False;
  }
  return True;
}

// Prepares and backs up the dev environment
function backupDev($site) {
  shell_exec("terminus connection:set $site.dev git --ansi -y");
	shell_exec("terminus env:clone-content $site.live dev --cc --updatedb --yes --ansi");
  shell_exec("terminus backup:create $site.dev --keep-for=180 --ansi");
}

// Prepares and backs up the test environment
function backupTest($site) {
  shell_exec("terminus env:clone-content $site.live test --cc --updatedb --yes --ansi");
  shell_exec("terminus backup:create $site.test --keep-for=180 --ansi");
}

// Backup live environment
function backupLive($site) {
	shell_exec("terminus backup:create $site.live --keep-for=180 --ansi");
}

// Prepares and backs up the specified multidev environment
function backupMultidev($site, $env) {
  shell_exec("terminus env:clone-content $site.live $env --cc --updatedb --yes --ansi");
  shell_exec("terminus backup:create $site.$env --keep-for=180 --ansi");
}

// Applies the upstream (core) updates to the specified site and environment
function applyUpstream($site, $env) {
  $upstreamUpdates = shell_exec("terminus upstream:updates:list $site.$env --format=csv --fields=hash");
  $upstreamUpdates = explode("\n", $upstreamUpdates);
  if (count($upstreamUpdates) > 2) {
    shell_exec("terminus upstream:updates:apply $site.$env --updatedb --ansi");
  } else {
    print "\e[1A\e[K ". color("[notice]", "white", "cyan")." Upstream for \"$env\" is up to date\n";
  }
}

// Opens both the site dashboard and the site admin (checks against OS as well)
function openSite($site, $env) {
  $url="https://$env-$site.pantheonsite.io/user";
  $unameOut = shell_exec("uname -s");
  shell_exec("terminus dashboard:view $site.$env --ansi");
  if ($unameOut == "Linux\n") {
    $open = shell_exec("xdg-open $url");
  } elseif ($unameOut == "Darwin\n") {
    shell_exec("open -n \"$url\"");
  } else {
    print "$url\n";
  }
	usleep(150000);
	print "\n";
}

// Does everything needed to prepare a multidev environment for updating
function prepMultidev($site, $env, $backup = True) {
	if (!envDoesExist($site, $env)) {
    return;
  }
  if (!canWork($site)) {
    return;
  }

  if ($backup == True) {
    backupLive($site);
    backupTest($site);
    backupDev($site);
    backupMultidev($site, $env);
  }

	shell_exec("terminus connection:set $site.$env git --ansi -y");
	shell_exec("terminus multidev:merge-from-dev $site.$env --ansi");
  applyUpstream($site, $env);
	shell_exec("terminus connection:set $site.$env sftp --ansi");

  openSite($site, $env);
}

/** Commits code changes from dev environment all the way to live environment.
 * Can be set to ignore validation or be set to work more closely with core
 * updates.
 */
function commitFromDev(
  $site, $message, $skipValidation = False, $forCoreUpdates = False) {

  // Checks for code that needs to be committed
  if ($skipValidation == False) {
    $commitStatus = shell_exec("terminus env:diffstat $site.dev --fields=file --format=csv");
    $commitStatus = explode("\n", $commitStatus);
    if (count($commitStatus) == 2 && $forCoreUpdates == False) {
      print "\e[1A\e[K ".color("[notice]", "white", "cyan")." No code to commit in ".color("dev", "green")." environment\n";
      return;
    }
  }

  // Deploys code from dev to test environments
  shell_exec('terminus env:deploy '.$site.'.test --note="'.$message.'" --cc --updatedb --ansi');

  /** When running core updates, this section checks to see if there was any
   * code that actually got committed and if not, it adds them to the failed
   * upstreams file
   */
  if ($forCoreUpdates == True) {
    $commitStatusTest = shell_exec("terminus env:code-log $site.dev --fields=datetime,message --format=csv");
    $commitStatusTest = explode("\n", $commitStatusTest);
    $commitStatusTest = $commitStatusTest[1];
    $commitStatusTestTemp = explode(",", $commitStatusTest);
    $commitTime = $commitStatusTestTemp[0];
    $commitMessage = $commitStatusTestTemp[1];
    $commitMessage = substr($commitMessage, 1, 16);
    $currentTime = date("Y-m-d");
    print "\e[1A\e[K";
    if (substr($commitTime, 0, 10) == $currentTime &&
    $commitMessage == "Update to Drupal") {
      $updateSuccess = True;
    } else {
      $updateSuccess = False;
    }

    // Checks if the update was successful or not
    if ($updateSuccess) {
      // Gets list of current failures
      $failures = array();
      $failedResource = fopen(__DIR__."/../siteInfo/failedUpstreams.txt", "r");
      if ($failedResource) {
        while (($line = fgets($failedResource, 4096)) !== False) {
          array_push($failures, preg_replace('/\n/', "", $line));
        }
      }
      fclose($failedResource);

      // Compares new success against existing failures
      $failuresTemp = array();
      if (in_array($site, $failures)) {
        for ($i=0; $i < count($failures); $i++) {
          if ($site !== $failures[$i]) {
            array_push($failuresTemp, $failures[$i]);
          }
        }
        $failures = $failuresTemp;
      }

      // Re-writes failures to failure file without successful update
      $failedResource = fopen(__DIR__."/../siteInfo/failedUpstreams.txt", "w");
      for ($i=0; $i < count($failures); $i++) {
        fwrite($failedResource, $failures[$i]."\n");
      }
      fclose($failedResource);
    } else {
      print "\e[1A\e[K ";
      print color("[notice]", "white", "cyan");
      print " Core has conflict, adding to conflict file\n\n";

      // Gets list of existing failures
      $failures = array();
      $failedResource = fopen(__DIR__."/../siteInfo/failedUpstreams.txt", "r");
      if ($failedResource) {
        while (($line = fgets($failedResource, 4096)) !== False) {
          array_push($failures, preg_replace('/\n/', "", $line));
        }
      }
      fclose($failedResource);

      // If the current failure isn't in the list it appends it to the file
      if (!in_array($site, $failures)) {
        $failedResource = fopen(__DIR__."/../siteInfo/failedUpstreams.txt","a");
        fwrite($failedResource, $site);
        fclose($failedResource);
      }

      return False;
    }
  }

  // Commit code from test to live environments
  shell_exec('terminus env:deploy '.$site.'.live --note="'.$message.'" --cc --updatedb --ansi');

  // Read siteSkip.txt into siteList
  $siteList = array();
  $siteSkipResource = fopen(__DIR__."/../siteInfo/siteSkip.txt", "r");
  if ($siteSkipResource) {
    while (($line = fgets($siteSkipResource, 4096)) !== False) {
      array_push($siteList, preg_replace('/\n/', "", $line));
    }
  }
  fclose($siteSkipResource);

  // Reformat siteList to account for both names and times
  for ($i=0; $i < count($siteList); $i++) {
    $siteListTemp = explode(" ", $siteList[$i]);
    $siteList[$i] = $siteListTemp[0];
    $siteListTimes[$i] = $siteListTemp[1];
  }

  print "\n";

  /** If the site is in the skipList, this makes sure it stays in, else this
   * just ends the script
   */
  $sitesToSkip = array();
  if (!in_array($site, $siteList)) {
    return;
  } else {
    for ($i=0; $i < count($siteList); $i++) {
      if ($site !== $siteList[$i]) {
        array_push($sitesToSkip, $siteList[$i]." ".$siteListTimes[$i]);
      }
    }
    $siteSkipResource = fopen(__DIR__."/../siteInfo/siteSkip.txt", "w");
    for ($i=0; $i < count($sitesToSkip); $i++) {
      fwrite($siteSkipResource, $sitesToSkip[$i]."\n");
    }
    fclose($siteSkipResource);
    return;
  }
}

/** This function commits code from a specified multidev environment all the way
 * to the live environment
 */
function commitFromMultidev($site, $env, $message) {
  if (gettype($message) == "array") {
    print " ". color("[error]", "white", "red")." Commit message can't be an array\n";
    return;
  }
  if (!envDoesExist($site, $env)) {
    return;
  }
  $commitStatus = shell_exec("terminus env:diffstat $site.$env --fields=file --format=csv");
  $commitStatus = explode("\n", $commitStatus);

  // If no code to commit adds the site to the siteSkip file
  if (count($commitStatus) == 2) {
    print "\e[1A\e[K ". color("[notice]", "white", "cyan")." No code to commit in ".color($env, "green")." environment\n";
    $siteList = array();
    $siteSkipResource = fopen(__DIR__."/../siteInfo/siteSkip.txt", "r");
    if ($siteSkipResource) {
      while (($line = fgets($siteSkipResource, 4096)) !== False) {
        array_push($siteList, preg_replace('/\n/', "", $line));
      }
    }
    fclose($siteSkipResource);

    // Split sitelist into names and times
    for ($i=0; $i < count($siteList); $i++) {
      $siteListTemp = explode(" ", $siteList[$i]);
      $siteList[$i] = $siteListTemp[0];
      $siteListTimes[$i] = $siteListTemp[1];
    }

    // Adds site to the siteSkip file
    $sitesToSkip = array();
    if (!in_array($site, $siteList)) {
      $siteSkipResource = fopen(__DIR__."/../siteInfo/siteSkip.txt", "a");
      fwrite($siteSkipResource, $site." ".date("Y-m-d\TH:i:s")."\n");
      fclose($siteSkipResource);
      return;
    } else {
      for ($i=0; $i < count($siteList); $i++) {
        if ($siteList[$i] !== $site) {
          array_push($sitesToSkip, $siteList[$i]." ".$siteListTimes[$i]);
        } else {
          array_push($sitesToSkip, $site." ".date("Y-m-d\TH:i:s"));
        }
      }
    }
    $siteSkipResource = fopen(__DIR__."/../siteInfo/siteSkip.txt", "w");
    for ($i=0; $i < count($sitesToSkip); $i++) {
      fwrite($siteSkipResource, $sitesToSkip[$i]."\n");
    }
    fclose($siteSkipResource);
    return;
  }

  // Commits changes
  shell_exec('terminus env:commit '.$site.'.'.$env.' --message="'.$message.'" --ansi');
  shell_exec('terminus multidev:merge-to-dev '.$site.'.'.$env.' --updatedb --ansi');
  commitFromDev($site, $message, True);
}

// Prep and update the core of the given site
function updateCore($site) {
  if (!siteDoesExist($site)) {
    print " ". color("[error]", "white", "red")." Site \"$site\" doesn't exist\n";
    return;
  }
  if (shell_exec("terminus upstream:update:status $site.dev") == "current\n") {
    print " ". color("[notice]", "white", "cyan")." Site \"$site\" is up to date\n";
    return;
  }
  backupLive($site);
  backupDev($site);
  applyUpstream($site, "dev");
  if (commitFromDev($site, "Core Updates", False, True) !== False) {
    openSite($site, "live");
  }
}

?>
