<?php
require_once __DIR__."/functions/macroFunctions.php";
require_once __DIR__."/functions/pantheonFunctions.php";
require_once __DIR__."/functions/listFunctions.php";

// Let the user determine whether or not to backup sites
function setUpdateType($isError = False) {
  print "Back up sites? [y] or [n]?\n";
	$updateType = readline(":");
  print "\n";
  if ($updateType == "y") {
    return True;
  } elseif ($updateType == "n") {
    return False;
  } else {
      if ($isError == True) {
        print "\e[1A\e[K";
      }
			print "\e[1A\e[K\e[1A\e[K\e[1A\e[KPlease enter a valid input\n";
			return setUpdateType(True);
	}
}

// Open header and prep sites for updates
headers(array(array("Site Security Updates!")), array(array(1,0)));
$updateType = setUpdateType();
setSiteSelection("siteList", "How many sites to update?", "Which sites?", $list);

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
      array("Setting up [".$currentItem."] (".$currentIncrement." / ".$listSize.")"),
      listFinalGet($list, True)
    ), array(
      array(1,0),
      array(0,4)
    ));

    // Prep multidev for updates
    prepMultidev($currentItem, "updates", $updateType);

    $list->setFinished($currentIncrement);

    // Read siteList and remove the item that was just completed
    $siteList = array();
    $removeSiteResource = fopen(__DIR__."/siteInfo/siteList.txt", "r");
    if ($removeSiteResource) {
      while (($line = fgets($removeSiteResource, 4096)) !== False) {
        array_push($siteList, preg_replace('/\n/', "", $line));
      }
    }
    fclose($removeSiteResource);

    $afterUpdateList = array();
    for ($i=0; $i < count($siteList); $i++) {
      if ($siteList[$i] !== $currentItem) {
        array_push($afterUpdateList, $siteList[$i]);
      }
    }
    $siteListResource = fopen(__DIR__."/siteInfo/siteList.txt", "w");
    if ($siteListResource) {
      for ($i=0; $i < count($afterUpdateList); $i++) {
        fwrite($siteListResource, $afterUpdateList[$i]."\n");
      }
    }
    fclose($siteListResource);

    $siteListResource = fopen(__DIR__."/siteInfo/sitesToFinish.txt", "a");
    if ($siteListResource) {
      fwrite($siteListResource, $currentItem."\n");
    }
    fclose($siteListResource);

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
  array("All Sites Finished", "Here is a Summary of Sites Prepped for Updates"),
  listFinalGet($list, True)
), array(
  array(1,0),
  array(0,4)
));
 ?>
