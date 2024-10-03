<?php

require_once __DIR__."/../classes/listItems.php";
require_once __DIR__."/headerFunctions.php";

$list = new ListItems();

/** Creates the list of values off of two quesitons, the first asks how many
 * list items need to be created and the second asks what the each list item is
 * equal to
 */
function listCreate($firstMessage, $secondMessage, $list) {
  /** This prints out the first message provided and gets the equivalent on the
   * length of the list being created. In this case it is most often utilized to
   * get how many sites that are going to be updated or finished.
   */
  print $firstMessage."\n";
  $listLength = readline(":");
  if (is_numeric($listLength) == True) {
    settype($listLength, "int");
  } else {
    print "\e[1A\e[K\e[1A\e[K";
    return listCreate($firstMessage, $secondMessage, $list);
  }
  print "\n";

  /** This prints out the second message provided and gets the equivalent of
   * which items to include in the list. This repeats for the number of times
   * that the previous section dictates.
   */
  $i=0;
  print $secondMessage."\n";
  while ($i < $listLength) {
    $listItem = readline(":");
    if (strlen($listItem) > 0) {
      $i++;
      $list->listItemAdd($listItem);
      $list->labelsValuesCreate($i); // Needed becuase of only one return
    } else {
      print "\e[1A\e[K";
    }
  }
  print "\n";
  return $listLength;
}

// An easy access point to the listItems class for creating lists from arrays
function listCreateArray($itemList, $list) {
  $list->listCreateFromArray($itemList);
}

/** This function creates a nice clean output of the list of items, their labels
 * and if set to, it will also check in the updateNotes file to see if there are
 * any relevant comments that can be appended into the output.
 */
function listFinalGet($list, $checkForComments = False) {
  /** This wipes the final list and re-adds the currently available list items
   * and adds the labels to the final list as well.
   */
  $list->wipeFinalList();
  $list->appendToFinalList($list->getList());
  $labelsTemp = array();
  for ($i=0; $i < count($list->getLabelsValues()); $i++) {
    array_push($labelsTemp, $list->labelsReturn($i));
  }
  $list->appendToFinalList($labelsTemp);

  /** This checks for the comments if $checkForComments is set to True. This
   * iterates through all of the comments available in the document and if any
   * of them match the items in the list, it adds them to the final list. If a
   * given list item doesn't have any comments, it adds "--" in its' place.
   */
  if ($checkForComments == True) {
    $comments = array();
    $commentsTemp = array();
    $comment = fopen(__DIR__."/../siteInfo/updateNotes.txt", "r");
    while (($line = fgets($comment, 4096)) !== False) {
      array_push($commentsTemp, $line);
    }
    for ($j=0; $j < count($list->getList()); $j++) {
      for ($i=0; $i < count($commentsTemp); $i++) {
        $commentSplit = explode(" ", $commentsTemp[$i]);
        if ($commentSplit[0] == $list->getList($j + 1)) {
          $finalComment = substr($commentsTemp[$i],
          strlen($commentSplit[0]) + 1);
          $finalComment = preg_replace('/\n/', "", $finalComment);
          array_push($comments, $finalComment);
          $i += count($commentsTemp);
        } elseif ($i == (count($commentsTemp) - 1)) {
          array_push($comments, "--");
        }
      }
    }
    $list->appendToFinalList($comments);
  }

  // Returns the final list created
  return $list->getFinalList();
}

function optionSelector($list, $isError = False) {
  // Array of the options the user can select
  $options = array(
    "[c]:Continue", "[s]:Skip", "[f]:Finish", "[r]:Rename", "[a]:Add Item"
  );

  /** Prints out the question for the user to respond to about what to do with
   * next list item.
   */
  print "For [".$list->getList($list->getIncrement() + 1)."] ";
  print "(".($list->getIncrement() + 1)." / ".$list->getListSize().")";
  print " what would you like to do?\n";

  /** This goes through and preps the option selector of what the user can do.
   * This goes through each of the items int hte $options array and figures out
   * the length of each element and organizes it into a nicely organized
   * printout.
   *
   * Fun fact about this section, this was the first proof of concept/attempt at
   * builing a table view that eventually became all of the work housed in the
   * tableFunctions.php file.
   */
  $printCounter = 0;
  $iterationAmount = 0;
  $optionsSize = count($options);
  foreach ($options as $i) {
    $length = strlen($i);
    if ($length > 15) {
      $i = substr($i, 0, 15);
    } else {
      while ($length < 15) {
        $i .= " ";
        $length++;
      }
    }
    $i .= " ";
    /** This section determines the way the options are printed, this checks if
     * three items have been printed, and if so, it sends the next item to the
     * next line.
     */
    print $i;
    $printCounter++;
    $iterationAmount++;
    if ($printCounter == 3 && $iterationAmount != $optionsSize) {
      print "\n"; $printCounter = 0;
    }
  }
  print "\n";

  /** Gets a user response and runs it against a number of checks in order to
   * determine what the next course of action is.
   */
  $response = readline(":");
  switch ($response) {
    // Continue option
    case 'c':
      $list->setScheduled($list->getIncrement() + 1);
      break;

    // Skip option
    case 's':
      $list->setSkipped($list->getIncrement() + 1);
      break;

    // Finish now option (skip all remaining)
    case 'f':
      while ($list->getIncrement() < $list->getListSize()) {
        $list->setSkipToEnd($list->getIncrement() + 1);
      }
      break;

    // Rename option
    case 'r':
      $list->listItemRename(listFinalGet($list, True), $list);
      optionSelector($list);
      break;

    // Add item option
    case 'a':
      $list->listItemAddManual(listFinalGet($list, True), $list);
      optionSelector($list);
      break;

    // Default option if all else fails
    default:
      if ($isError == True) {
        print "\e[1A\e[K";
      }
      print "\e[1A\e[K\e[1A\e[K\e[1A\e[K\e[1A\e[KPlease enter a valid input\n";
      return optionSelector($list, True);
      break;
  }
}

/** This function serves as a preparation for the creation of a list from an
 * array, this gets the list length. This can either get a specific number of
 * items, or it can get all of them (9999).
 */
function listCreateArrayPrep($message, $list, $isError = False) {
  print $message." [a]:All or [#]\n";
  $listLength = readline(":");
  print "\n";
  if (is_numeric($listLength) == True) {
    settype($listLength, "int");
  } elseif ($listLength == "a") {
    return 9999;
  } else {
    if ($isError == True) {
      print "\e[1A\e[K";
    }
    print "\e[1A\e[K\e[1A\e[K\e[1A\e[KPlease enter a valid input\n";
    return listCreateArrayPrep($message, $list, True);
  }
  return $listLength;
}

/** This checks with the user and determines whether or not the list needs to be
 * inverted (Making the first item the last the second item the second to last
 * etc.). If the user responsed with yes, True is returned, and if no, False is
 * is returned.
 */
function invertSelection($listLength, $isError = False) {
  if ($listLength > 1) {
    print "Invert update order? [y] or [n]\n";
    $updateOrder = readline(":");
    print "\n";
    if ($updateOrder == "y") {
      return True;
    } elseif ($updateOrder == "n") {
      return False;
    } else {
      if ($isError == True) {
        print "\e[1A\e[K";
      }
      print "\e[1A\e[K\e[1A\e[K\e[1A\e[KPlease enter a valid input\n";
      return invertSelection($listLength, True);
    }
  }
}

/** This determines whether or not the user would like to omit some of the list
 * items from their list. This is useful in cases when you want to use the
 * second set of 5 items instead of the first 5. 10 items could be selected and
 * then 5 items could be skipped to give the desired effect.
 */
function skipItems($isError = False) {
  print "Do you want to skip items? [y] or [n]\n";
  $skipResponse = readline(":");
  print "\n";
  if ($skipResponse == "y") {
    return True;
  } elseif ($skipResponse == "n") {
    return False;
  } else {
    if ($isError == True) {
      print "\e[1A\e[K";
    }
    print "\e[1A\e[K\e[1A\e[K\e[1A\e[KPlease enter a valid input\n";
    return skipItems(True);
  }
}

/** This works hand in hand with skipItems() and determines the amount of items
 * to skip based on user input.
 */
function howManyToSkip($isError = False) {
  print "How many do you want to skip? [#]\n";
  $skipAmount = readline(":");
  print "\n";
  if (is_numeric($skipAmount) == True) {
    settype($skipAmount, "int");
    return $skipAmount;
  } else {
    if ($isError == True) {
      print "\e[1A\e[K";
    }
    print "\e[1A\e[K\e[1A\e[K\e[1A\e[KPlease enter a valid input\n";
    return howManyToSkip(True);
  }
}

/** This function helps to determine which of the above functions to call and
 * what the general direction the item selection is going to take.
 */
function setSiteSelection(
  $fileName, $firstMessage, $secondMessage, $list, $isError = False) {

  /** This first determines whether or not to have an auto-generated list of
   * items or to have a manually built one.
   */
  print "Manually select sites? [y] or [n]?\n";
  $updateType = readline(":");
  print "\n";
  // If manual is selected
  if ($updateType == "y") {
    listCreate($firstMessage, $secondMessage, $list);
  /** If automatic is selected it then takes the top items from the $fileName
   * input and gets the amount received in listCreateArrayPrep().
   */
  } elseif ($updateType == "n") {
    $listLength = listCreateArrayPrep($firstMessage, $list);
    $siteList = array();
    $siteListResource = fopen(__DIR__."/../siteInfo/$fileName.txt", "r");
    if ($siteListResource) {
      while (($line = fgets($siteListResource, 4096)) !== False) {
        array_push($siteList, preg_replace('/\n/', "", $line));
      }
    }
    fclose($siteListResource);
    array_splice($siteList, $listLength);

    // Checks for items to be skipped and if there are any, skips them
    if (skipItems()) {
      $skipAmount = howManyToSkip();
      $siteListTemp = array();
      for ($i=$skipAmount; $i < count($siteList); $i++) {
        array_push($siteListTemp, $siteList[$i]);
      }
      $siteList = $siteListTemp;
    }

    // Checks for inverting selection and if there is, inverts the list
    if (invertSelection($listLength)) {
      $siteListTemp = array();
      for ($i=(count($siteList) - 1); $i >= 0; $i--) {
        array_push($siteListTemp, $siteList[$i]);
      }
      $siteList = $siteListTemp;
    }

    // Creates the list
    listCreateArray($siteList, $list);

  // If invalid input is given, then the function calls itself recursively
  } else {
      if ($isError == True) {
        print "\e[1A\e[K";
      }
			print "\e[1A\e[K\e[1A\e[K\e[1A\e[KPlease enter a valid input\n";
			return setSiteSelection($fileName, $firstMessage,
      $secondMessage, $list, True);
	}
}


 ?>
