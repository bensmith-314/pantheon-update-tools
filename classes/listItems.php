<?php
require_once __DIR__."/../functions/utilityFunctions.php";
require_once __DIR__."/../functions/tableFunctions.php";
require_once __DIR__."/../functions/listFunctions.php";

class ListItems {
  private $list = array();
  private $currentIncrement = 1;
  private $labels = array(
    "[Scheduled]", "[In Progress]", "[Skipped]", "[Skip to End]", "[Finished]"
  );
  private $labelsValues = array();
  private $comments = array();
  private $finalList = array();

  // Used to add a single item to the list
  function listItemAdd($item) {
    if (gettype($item) !== "string") {
      throw new InvalidArgumentException("\$item needs to be a string");
    }
    array_push($this->list, $item);
    return $this->list;
  }

  // Used to add an array of items to the list
  function listCreateFromArray($itemList) {
    for ($i=0; $i < count($itemList); $i++) {
      array_push($this->list, $itemList[$i]);
      $this->labelsValuesCreate($i + 1);
    }
    return $this->list;
  }

  /** This is for adding items to the list after the initial list creation when
   * in the middle of operations. This gives a nice interface to add as many
   * items as needed and then give an updated list of what the list looks like
   * after exiting when the options are then displayed.
   */
  function listItemAddManual($finalList, $list, $isError = False) {
    headers(array(array("Add an Item!"), $finalList), array(array(1,0), array(0,4)));
    // Asks question for getting a response from the user
    if ($isError == True) {
      print "Please enter a valid input.\n";
    }
    print "What would you like to add?\n";
    print "[x]:Exit\n";
    $addResponse = readline(":");
    print "\n";

    /** Depending on the response from the user, the function either exits and
     * displays the current status, re-calls the function to get an appropriate
     * response, or pushes the new item into the list and calls the function to
     * get new items or be exited.
     */
    if ($addResponse == "x") {
      headers(array(array("Current Status"), $finalList), array(array(1,0), array(0,4)));
      return $this->list;
    } elseif (strlen($addResponse) == 0) {
      $this->listItemAddManual($finalList, $list, True);
    } else {
      array_push($this->list, $addResponse);
      array_push($this->labelsValues, 0);
      $this->listItemAddManual(listFinalGet($list, True), $list);
    }
  }

  /** This is for renaming items in the list after the initial list creation
   * when in the middle of operations. This gives a nice interface to rename as
   * many items as needed and then give an updated list of what the list looks
   * like after exiting when the options are then displayed.
   */
  function listItemRename($finalList, $list, $isError = False) {
    headers(array(array("Rename and Item!"), $finalList), array(array(1,0), array(0,4)));
    // Asks question for getting a response from the user
    if ($isError == True) {
      print "Pease enter a valid input\n";
    }
    print "Which item would you like to rename?\n";
    print "[x]:Exit\n";
    $renameResponse = readline(":");
    print "\n";

    /** Depending on the response from the user, the function either exits and
     * displays the current status, re-calls the function to get an appropriate
     * response, or renames the item into the list and calls the function to
     * rename other items or be exited.
     */
    if ($renameResponse == "x") {
      headers(array(array("Current Status"), $finalList), array(array(1,0), array(0,4)));
      return $this->list;
    } elseif ($renameResponse == 0 || $renameResponse <= $this->currentIncrement || $renameResponse > count($this->list)) {
      $this->listItemRename(listFinalGet($list, True), $list, True);
    } elseif ($renameResponse != 0 && $renameResponse > $this->currentIncrement && $renameResponse <= count($this->list)) {
      print "What would you like to rename [" . $this->list[$renameResponse - 1] . "] to?\n";
      $this->list[$renameResponse - 1] = readline(":");
      $this->listItemRename(listFinalGet($list, True), $list);
    }
  }

  /** Sets the label value of the list item at the given index to 0. This is
   * needed becuase of only one return in listcreate().
   */
  function labelsValuesCreate($index) {
    if (gettype($index) !== "integer") {
      throw new InvalidArgumentException("\$index needs to be an integer");
    }
    $this->labelsValues[$index - 1] = 0;
    return $this->labelsValues;
  }

  // Checks if the index given is out of bounds and if not it returns the label
  function labelsReturn($index) {
    $labelsLength = sizeof($this->labels) - 1;
    if ($this->labelsValues[$index] == -1) {
      return "[Out of Bounds]";
    } else {
      $index = $this->labelsValues[$index];
      return $this->labels[$index];
    }
  }

  // Wipes the $finalList to get it ready for new creation of $finalList.
  function wipeFinalList() {
    $this->finalList = array();
    return $this->finalList;
  }

  /** Depending on whether or not an index is given, this function returns the
   * entire list (no index provided), or a specified index.
   */
  function getList($index = -1) {
    if ($index == -1) {
      return $this->list;
    } else {
      return $this->list[$index - 1];
    }
  }

  // Returns the size of the list.
  function getListSize() {
    return count($this->list);
  }

  // Returns the $finalList.
  function getFinalList() {
    return $this->finalList;
  }

  // Returns all labal values
  function getLabelsValues() {
    return $this->labelsValues;
  }

  // Returns only one label value specified in the $index
  function getLabelValue($index) {
    return $this->labelsValues[$index - 1];
  }

  // Returns the labels
  function getLabels() {
    return $this->labels;
  }

  // Appends the given value to the end of the $finalList
  function appendToFinalList($valueToAppend) {
    array_push($this->finalList, $valueToAppend);
  }

  // Returns the current increment of the opject
  function getIncrement() {
    return $this->currentIncrement;
  }

  // Increases the current increment by 1
  function incrementAdd() {
    $this->currentIncrement++;
    return $this->currentIncrement;
  }

  /** Sets the given index's label value to the value given. This has some
   * validation built in, but this function is rarely used when the below
   * functions accomplish similar things with less ambiguity.
   */
  function setLabelsValues($index, $value) {
    if (gettype($index) !== "integer" && gettype($value) !== "integer") {
      return;
    }
    if ($value < 0 || $value > 4) {
      return;
    }
    if ($index > count($this->labelsValues)) {
      return;
    }
    $this->labelsValues[$index] = $value;
    return $this->labelsValues;
  }

  // Sets the label value of the given index to scheduled.
  function setScheduled($index) {
    if (gettype($index) !== "integer") {
      return;
    }
    if ($index > count($this->labelsValues)) {
      return;
    }
    $this->labelsValues[$index - 1] = 0;
    return $this->labelsValues;
  }

  // Sets the label value of the given index to in progress.
  function setInProgress($index) {
    if (gettype($index) !== "integer") {
      return;
    }
    if ($index > count($this->labelsValues)) {
      return;
    }
    $this->labelsValues[$index - 1] = 1;
    return $this->labelsValues;
  }

  // Sets the label value of the given index to skipped.
  function setSkipped($index) {
    if (gettype($index) !== "integer") {
      return;
    }
    if ($index > count($this->labelsValues)) {
      return;
    }
    $this->labelsValues[$index - 1] = 2;
    return $this->labelsValues;
  }

  // Sets the label value of the given index to skip to end.
  function setSkipToEnd($index) {
    if (gettype($index) !== "integer") {
      return;
    }
    if ($index > count($this->labelsValues)) {
      return;
    }
    $this->labelsValues[$index - 1] = 3;
    $this->currentIncrement += 1;
    if ($this->currentIncrement < count($this->labelsValues)) {
      $this->setSkipToEnd($index + 1);
    }
  }

  // Sets the label value of the given index to finished.
  function setFinished($index) {
    if (gettype($index) !== "integer") {
      return;
    }
    if ($index > count($this->labelsValues)) {
      return;
    }
    $this->labelsValues[$index - 1] = 4;
  }
}


?>
