<?php
require_once __DIR__."/pantheonFunctions.php";
require_once __DIR__."/headerFunctions.php";
require_once __DIR__."/dateFunctions.php";
require_once __DIR__."/listFunctions.php";

/** This function takes a look into the pantheon database and finds all of the
 * sites that need updates and in what order they need to be updated and puts
 * them in a file to be accessed.
 */
function updateSiteList() {
  // Opening Header
  headers(array(array("Determining Sites Able To Update!")), array(array(1,0)));

  // List of upstreams and their names that need to be updated
  $upstreams = array(
    "21e1fada-199c-492b-97bd-0b36b53a9da0",
    "2e592dbc-b5a2-4bd9-972e-1436be44b07c",
    "8a129104-9d37-4082-aaf8-e6f31154644e"
  );
  $upstreamNames = array(
    "Drupal 7",
    "Tribute Media D7 Base",
    "Drupal 8"
  );

  /** Checks the siteIgnore file and adds any sites in that file into an ignore
   * array that will remove any sites that are gotten later.
   */
  $siteIgnore = array();
  $siteIgnoreResource = fopen(__DIR__."/../siteInfo/siteIgnore.txt", "r");
  if ($siteIgnoreResource) {
    while (($line = fgets($siteIgnoreResource, 4096)) !== False) {
      array_push($siteIgnore, $line);
    }
  }
  fclose($siteIgnoreResource);

  /** Checks the siteSkip file for any sites that need to be skipped due to an
   * issue with updating (no modules to update, wouldn't update, changes
   * wouldn't appear, or needed to skip for other reasons) and pulls that
   * information into an array to be parsed.
   */
  $siteSkip = array();
  $siteSkipResource = fopen(__DIR__."/../siteInfo/siteSkip.txt", "r");
  if ($siteIgnoreResource) {
    while (($line = fgets($siteSkipResource, 4096)) !== False) {
      array_push($siteSkip, $line);
    }
  }
  fclose($siteSkipResource);

  /** This parses through the array created in the previous code block and
   * removes any leading or trailng whitespaces and then splits the array into
   * names and dates respectively.
   */
  $siteSkipNames = array();
  $siteSkipDates = array();
  for ($i=0; $i < count($siteSkip); $i++) {
    $siteSkip[$i] = preg_replace('/\n/', "", $siteSkip[$i]);
    // Removes leading white space
    while (substr($siteSkip[$i], 0, 1) == " ") {
      $siteSkip[$i] = substr($siteSkip[$i], 1, strlen($siteSkip[$i]));
    }
    // Removes trailing white space
    while (substr($siteSkip[$i], strlen($siteSkip[$i]) - 1,
    strlen($siteSkip[$i])) == " ") {
      $siteSkip[$i] = substr($siteSkip[$i], 0, strlen($siteSkip[$i]) - 1);
    }
    array_push($siteSkipNames,
    substr($siteSkip[$i], 0, strlen($siteSkip[$i]) - 20));
    array_push($siteSkipDates,
    substr($siteSkip[$i], strlen($siteSkip[$i]) - 19, strlen($siteSkip[$i])));
  }

  // Gets the siteList from the upstreams
  $sites = array();
  for ($i=0; $i < count($upstreams); $i++) {
    print " ".color("[notice]", "white", "cyan");
    print " Getting sites for $upstreamNames[$i]\n";
    $sitesTempCmd = "terminus site:list --fields=name --format=csv";
    $sitesTempCmd .= " --upstream=$upstreams[$i]";
    $sitesTemp = shell_exec($sitesTempCmd);
    print "\e[1A\e[K"." ".color("[notice]", "white", "cyan");
    print " Got sites for $upstreamNames[$i]\n";
    $sitesTemp = explode("\n", $sitesTemp);
    for ($j=1; $j < (count($sitesTemp) - 1); $j++) {
      array_push($sites, $sitesTemp[$j]);
    }
  }
  print "\e[1A\e[K\e[1A\e[K\e[1A\e[K";

  // Determines teh length of the longest siteName for use in the table
  $sitesLen = 0;
  for ($i=0; $i < count($sites); $i++) {
    if (strlen($sites[$i]) > $sitesLen) {
      $sitesLen = strlen($sites[$i]);
    }
  }

  /** Checks if the site is able to be updated and if so, adds to an array and
   * then prints it out in a three column table view
   */
  $sitesAvailable = array();
  for ($i=0; $i < count($sites); $i++) {
    if (in_array($sites[$i]."\n", $siteIgnore)) {
      $message = array(
        "(".($i + 1)." / ".count($sites).")",$sites[$i],"[Unable to Update]");
    } elseif (canWork($sites[$i], 0)) {
      array_push($sitesAvailable, $sites[$i]);
      $message = array(
        "(".($i + 1)." / ".count($sites).")",$sites[$i],"[Can be Updated]");
    } else {
      $message = array(
        "(".($i + 1)." / ".count($sites).")",$sites[$i],"[Unable to Update]");
    }
  	$messages = array();
  	array_push($messages, $message);

  	$arrayLens = array(11, $sitesLen, 18);
  	print "|";
  	for ($j=0; $j < 3; $j++) {
  		print " ";
  		$length = strlen($messages[0][$j]);
  		while ($length < $arrayLens[$j]) {
  			$messages[0][$j] .= " ";
  			$length++;
  		}
  		print $messages[0][$j]." |";
  	}
  	print "\n";
  }

  // Header for second section
  headers(array(array("Determining Sites Update Timeline")), array(array(1,0)));

  /** This determines how recently the given site was updated by taking a look
   * at the code log. This parses through all of the commits until it finds a
   * match (if one exists)
   */
  $sitesTimes = array();
  for ($i=0; $i < count($sitesAvailable); $i++) {
    $updateLogs = "";
    $updateLogsCmd = "terminus env:code-log ".$sitesAvailable[$i];
    $updateLogsCmd .= ".live --fields=datetime,message --format=csv";
    $updateLogs = shell_exec($updateLogsCmd);
    $updateLogs = explode("\n", $updateLogs);
    // Takes dates based off of skip file if necessary
    if (in_array($sitesAvailable[$i], $siteSkipNames)) {
      for ($j=0; $j < count($siteSkipNames); $j++) {
        if ($sitesAvailable[$i] == $siteSkipNames[$j]) {
          array_push($sitesTimes, $siteSkipDates[$j]);
          $message = array(
            "(".($i + 1)." / ".count($sitesAvailable).")",
            $sitesAvailable[$i], $siteSkipDates[$j]);
          $j += count($siteSkipNames);
        }
      }
    // Checks the commit log for times
    } else {
      for ($j=0; $j < count($updateLogs); $j++) {
        if (substr($updateLogs[$j], 21, 42) ==
        "Update Modules  Merged updates into master") {
          array_push($sitesTimes, substr($updateLogs[$j], 0, 19));
          $message = array(
            "(".($i + 1)." / ".count($sitesAvailable).")",
            $sitesAvailable[$i], substr($updateLogs[$j], 0, 19));
          $j += count($updateLogs);
        } elseif ($j == count($updateLogs) - 1) {
          array_push($sitesTimes, "0000-00-00T00:00:00");
          $message = array(
            "(".($i + 1)." / ".count($sitesAvailable).")",
            $sitesAvailable[$i], "0000-00-00T00:00:00");
        }
      }
    }

    // Prints out the result in a three column layout
    $messages = array();
    array_push($messages, $message);
    $arrayLens = array(11, $sitesLen, 19);

    print "|";
    for ($j=0; $j < 3; $j++) {
      print " ";
      $length = strlen($messages[0][$j]);
      while ($length < $arrayLens[$j]) {
        $messages[0][$j] .= " ";
        $length++;
      }
      print $messages[0][$j]." |";
    }
    print "\n";
  }

  // Summary header and summary printout from date sorting
  headers(array(array(
    "Site List is Up to Date", "Here is a Summary of the Refresh")),
    array(array(1,0))
  );
  $finalSites = dateDiff($sitesAvailable, $sitesTimes);

  /** Moves the current siteList file into the archive, and creates a new one
   * based off of the newly generated list.
   */
  $finalSitesCmd = "mv ".__DIR__."/../siteInfo/siteList.txt ";
  $finalSitesCmd .= __DIR__."/../siteInfo/listArchive/siteList_";
  $finalSitesCmd .= date("Y-m-d\TH-i-s").".txt";
  shell_exec($finalSitesCmd);
  $newListPath = __DIR__."/../siteInfo/siteList.txt";
  $newList = fopen($newListPath, "w");
  for ($i=0; $i < count($finalSites); $i++) {
    fwrite($newList, $finalSites[$i]."\n");
  }
  fclose($newList);
  $archiveSize = shell_exec("ls -l ".__DIR__."/../siteInfo/listArchive/ | wc -l");
  while ($archiveSize > 6) {
    $archive = shell_exec("ls -g ".__DIR__."/../siteInfo/listArchive/");
    $archive = explode("\n", $archive);
    $archiveTemp = explode(" ", $archive[1]);
    $index = count($archiveTemp) - 1;
    $unlinkCmd = __DIR__."/../siteInfo/listArchive/".$archiveTemp[$index];
    unlink($unlinkCmd);
    $archiveSize = shell_exec("ls -l ".__DIR__."/../siteInfo/listArchive/ | wc -l");
  }
}

/** Gets user input and determines which upstream to pick based on the given
 * upstreams. This returns the $upstreamID.
 */
function determineUpstream($upstreams, $isError = False) {
  print "Which upstream do you want to run updates for?\n";
  foreach ($upstreams as $key => $upstreamID) {
    print "[$key] ";
  }
  print "\n";
  $upstreamType = readline(":");
  foreach ($upstreams as $key => $upstreamID) {
    if (strcasecmp($upstreamType, $key) == 0) {
      return $upstreamID;
    }
  }

  if ($isError == True) {
    print "\e[1A\e[K";
  }
  print "\e[1A\e[K\e[1A\e[K\e[1A\e[KPlease enter a valid input\n";
  return determineUpstream($upstreams, True);
}

/** This function goes through all of the upstreams and prepares them and runs
 * core updates on them
 */
function massUpdateCore($list) {
  $upstreams = array(
    "D7" => "21e1fada-199c-492b-97bd-0b36b53a9da0",
    "TMD7" => "2e592dbc-b5a2-4bd9-972e-1436be44b07c",
    "D8" =>"8a129104-9d37-4082-aaf8-e6f31154644e"
  );
  $upstreamNames = array(
    "Drupal 7",
    "Tribute Media D7 Base",
    "Drupal 8"
  );

  // Determines which upstream to run core updates on
  headers(array(array("Drupal Core Updater")), array(array(1,0)));
  $upstreamID = determineUpstream($upstreams);

  /** Determines sites in the upstream that may or may not need updates (writes
   * to a file to prevent outputing a message).
   */
  headers(array(array(
    "Determining Which Sites Need Upstream Updates")), array(array(1,0)));
  $siteListTempCmd = "terminus site:list --format=csv --fields=name,plan_name,";
  $siteListTempCmd .= "frozen --upstream=".$upstreamID." >> ".__DIR__;
  $siteListTempCmd .= "/functionFiles/currentSites.txt";
  $siteListTemp = shell_exec($siteListTempCmd);

  // Opens and reads the file that was just created. Then deletes afterwards
  $currentSitesRaw = array();
  $currentSitesResource = fopen(__DIR__."/functionFiles/currentSites.txt", "r");
  if ($currentSitesResource) {
    while (($line = fgets($currentSitesResource, 4096)) !== False) {
      array_push($currentSitesRaw, $line);
    }
  }
  fclose($currentSitesResource);
  unlink(__DIR__."/functionFiles/currentSites.txt");

  // Strip newline characters that come from the file
  $siteListTemp = preg_replace('/\n/', "", $currentSitesRaw);

  // Split the array for determining if the site can have core updated
  $siteList = array();
  for ($i=0; $i < (count($siteListTemp) - 1); $i++) {
    $siteList[$i] = explode(",", $siteListTemp[$i]);
  }

  // Verify site is able to have core updated
  $currentSitesList = array();
  for ($i=1; $i < count($siteList); $i++) {
    if ($siteList[$i][1] != "Sandbox" &&
        $siteList[$i][2] == "false") {
          array_push($currentSitesList, $siteList[$i][0]);
        }
  }

  // Determine the longest site name length for table view
  $sitesLen = 0;
  for ($i=0; $i < count($currentSitesList); $i++) {
    if (strlen($currentSitesList[$i]) > $sitesLen) {
      $sitesLen = strlen($currentSitesList[$i]);
    }
  }

  /** Checks each site to see if there are available core updates that need to
   * be updated, in which case it would added to a the $finalSiteList. Otherwise
   * it is ignored. */
  $finalSiteList = array();
  $messages = array();
  for ($i=0; $i < count($currentSitesList); $i++) {
    $statCmd = "terminus upstream:update:status ".$currentSitesList[$i].".dev";
    if (shell_exec($statCmd) == "outdated\n") {
      array_push($finalSiteList, $currentSitesList[$i]);
      $message = array(
        "(".($i + 1)." / ".count($currentSitesList).")",
        $currentSitesList[$i], "[Needs Update]");
    } else {
      $message = array(
        "(".($i + 1)." / ".count($currentSitesList).")",
        $currentSitesList[$i], "[Up To Date]");
    }
  	$messages = array();
  	array_push($messages, $message);

    // Print out table view of site status'
  	$arrayLens = array(11, $sitesLen, 14);
  	print "|";
  	for ($j=0; $j < 3; $j++) {
  		print " ";
  		$length = strlen($messages[0][$j]);
  		while ($length < $arrayLens[$j]) {
  			$messages[0][$j] .= " ";
  			$length++;
  		}
  		print $messages[0][$j]." |";
  	}
  	print "\n";
  }

  /** Checks to see if there is at least 1 site that needs an update and if
   * there is, it starts the list creation process and then goes through each
   * site and updates them. Otherwise, the function ends.
   */
  if (count($finalSiteList) > 0) {
    // Create list from array
    listCreateArray($finalSiteList, $list);

    // Iterate through each list element to update core
    while (($list->getIncrement() - 1) < $list->getListSize()) {
      $currentIncrement = $list->getIncrement();
      $listSize = $list->getListSize();
      $itemsRemaining = $listSize - $currentIncrement;
      $currentLabelValue = $list->getLabelValue($currentIncrement);
      $currentItem = $list->getList($currentIncrement);

      // Check current label value and header
      if ($currentLabelValue == 0) {
        $list->setInProgress($currentIncrement);
        headers(array(
          array("Setting up [".$currentItem."] (".
          $currentIncrement." / ".$listSize.")"),
          listFinalGet($list)
        ), array(
          array(1,0),
          array(0,4)
        ));

        // Update core
        updateCore($currentItem);

        $list->setFinished($currentIncrement);

        // If more than one site remain, show optionSelector()
        if ($itemsRemaining > 0) {
          optionSelector($list);
        }

        $list->incrementAdd();
      // Extra validation when skipping sites
      } elseif ($currentLabelValue == 2 && $currentIncrement < $listSize) {
        print "\n";
        optionSelector($list);
        $list->incrementAdd();
      } elseif ($currentLabelValue == 2 && $currentIncrement == $listSize) {
        $list->incrementAdd();
        $list->setSkipped($currentIncrement + 1);
      }
    }

    // Summary header
    headers(array(
      array("All Sites Finished",
      "Here is a Summary of Sites Prepped for Updates"),
      listFinalGet($list)
    ), array(
      array(1,0),
      array(0,4)
    ));
  } else {
    headers(array(array("All Sites Up To Date")), array(array(1,0)));
  }
}
 ?>
