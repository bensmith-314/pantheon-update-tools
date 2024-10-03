<?php
require_once __DIR__."/functions/macroFunctions.php";
require_once __DIR__."/functions/pantheonFunctions.php";
require_once __DIR__."/functions/listFunctions.php";

// Open header and prep list for commiting changes
headers(array(array("Commit Changes from Multidev!")), array(array(1,0)));
setSiteSelection("sitesToFinish", "How many sites to commit changes?", "Which sites?", $list);

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
      array("Commiting [".$currentItem."] (".$currentIncrement." / ".$listSize.")"),
      listFinalGet($list)
    ), array(
      array(1,0),
      array(0,4)
    ));

    // Commit changes
    commitFromMultidev($currentItem, "updates", "Update Modules");

    $list->setFinished($currentIncrement);

    // Read sitesToFinish and remove the item that was just completed
    $siteList = array();
    $removeSiteResource = fopen(__DIR__."/siteInfo/sitesToFinish.txt", "r");
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
    $siteListResource = fopen(__DIR__."/siteInfo/sitesToFinish.txt", "w");
    if ($siteListResource) {
      for ($i=0; $i < count($afterUpdateList); $i++) {
        fwrite($siteListResource, $afterUpdateList[$i]."\n");
      }
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

// Summary Header
headers(array(
  array("All Sites Finished", "Here is a Summary of Which Sites had Code Committed"),
  listFinalGet($list, False)
), array(
  array(1,0),
  array(0,4)
));
 ?>
