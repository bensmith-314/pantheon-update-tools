<?php

require_once __DIR__ . "/utilityFunctions.php";

/* Function getMaxLen takes in an array input and outputs an interger
 * Input $arrayToCheck is an array that will be processed to see what the max
 * string length of the input array is. given the array("Hello", "World!",
 * "You are ", "amazing!!!") getMaxLen will return 10 because the string
 * 'amazing!!!' is 10 characters long.
 * This is a critical component of helping determine the widths of columns in
 * the table view by giving the function an idea of what the best formatting may
 * be in terms of space allocation of columns.
 */
// Creates $maxLen outside the function so it can be accessed when returned
$maxLen;
function getMaxLen($arrayToCheck) {
  // Resets $maxLen for multiple uses so output is always accurate
  $maxLen = 0;
  for ($i=0; $i < count($arrayToCheck); $i++) {
    if (strlen($arrayToCheck[$i]) > $maxLen) {
      $maxLen = strlen($arrayToCheck[$i]);
    }
  }
  return $maxLen;
}

/* This function is called when there are elements that are too long for the
 * section.
 * This funciton needs the following inputs:
 *   $inputArray (two dimensional)
 *   $j (current row of the table)
 *   $columnLens (widths of the sections)
 *   $internal (Whether or not this is called from inside the function)
 */
function isTooLongInternal($inputArray, $j, $columnLens, $internal = "False") {
  // Resets $thisLine and $nextLine
  $thisLine = "||";
  $nextLine = "";
  /* If function is called from inside itself, this sets the row line to zero
   * (necessary due to the way the function formats its' next input) and also
   * provides a prefix for a visual separation between top level elements and
   * secondary (and beyond) elements.
   */
  if ($internal == "True") {
    $j = 0;
    for ($i=0; $i < count($inputArray); $i++) {
      $inputArray[$i][0] = "  ".$inputArray[$i][0];
    }
  }

  /* This iterates through each element of a row and formats the current line as
   * needed then adds any excess content to the next line which eventually gets
   * passed through this function again until all parts of each element has been
   * printed in the proper format
   */
  for ($k=0; $k < count($inputArray); $k++) {
    $inputSplit = array();
    $thisLine .= " ";
    $thisLineTemp = "";

    /* Checks for empty elements that occur after being called a second time
     * and then prepares for printing
     */
    if (strlen($inputArray[$k][$j] == 1 && $inputArray[$k][$j] == " ")) {
      $length = strlen($inputArray[$k][$j]);
      while ($length < $columnLens[$k]) {
        $inputArray[$k][$j] .= " ";
        $length++;
      }
      $thisLine .= $inputArray[$k][$j]." |";
    /* Checks if string is already short enough and if so formats and prepares
     * for printing
     */
    } elseif (strlen($inputArray[$k][$j]) <= $columnLens[$k]) {
      $length = strlen($inputArray[$k][$j]);
      while ($length < $columnLens[$k]) {
        $inputArray[$k][$j] .= " ";
        $length++;
      }
      $thisLine .= $inputArray[$k][$j]." |";
      $nextLine .= "|";
    /* If additional formatting is required, this portion is called and first
     * explodes the input element into an array that is then evaluated as it
     * is stiched back together
     */
    } else {
      $inputSplit = explode(" ", $inputArray[$k][$j]);

      /* Checks if the first word in the exploded array is too long for the
       * section and if it is, cuts it down and hypenates the word across the
       * next line.
       */
      if (strlen($inputSplit[0]) > $columnLens[$k] && $internal == "False") {
        $thisLine .= substr($inputSplit[0], 0, $columnLens[$k] - 1)."- |";
        $nextLine .= substr($inputSplit[0], $columnLens[$k] - 1,
        strlen($inputSplit[0]))." ";
        for ($l=1; $l < count($inputSplit); $l++) {
          $nextLine .= $inputSplit[$l]." ";
        }
        $nextLine .= "|";

      /* Checks if the first word on a new line in the exploded array is too
       * long and cuts it down and hyphenates the word across the next line.
       */
      } elseif (strlen($inputSplit[2]) > $columnLens[$k] && $internal == "True") {
        $thisLine .= "  ".substr($inputSplit[2], 0, $columnLens[$k] - 3)."- |";
        $nextLine .= substr($inputSplit[2], $columnLens[$k] - 3,
        strlen($inputSplit[2]))." ";
        for ($l=3; $l < count($inputSplit); $l++) {
          $nextLine .= $inputSplit[$l]." ";
        }
        $nextLine .= "|";
      /* If there are more than 1 element after this point, this section of code
       * is called. It iterates through each element and formats into $thisLine
       * and $nextLine as needed for the proper formatting.
       */
      } elseif (count($inputSplit) > 0) {
        for ($l=0; $l < count($inputSplit); $l++) {
          // Checks if appending additional words is ok to do with column width
          if (strlen($thisLineTemp.$inputSplit[$l]) < $columnLens[$k]) {
            $thisLineTemp .= $inputSplit[$l]." ";
          } else {
            while ($l < count($inputSplit)) {
              $nextLine .= $inputSplit[$l]." ";
              $l++;
            }
          }
        }
        // Appends trailing white space to $thisLine to flush out table format
        $length = strlen($thisLineTemp);
        while ($length < $columnLens[$k]) {
          $thisLineTemp .= " ";
          $length++;
        }
        // Appends section splitting
        $thisLine .= $thisLineTemp." |";
        $nextLine .= "|";

      }
    }
  }
  // This prints out the completely formatted $thisline
  print $thisLine."|\n";

  /* This determines whether or not to recursively call the function to take
   * care of $nextLine based on whether or not $nextLine is longer than the
   * number of columns (which are used as a delimeter between sections). If
   * there is more content to be taken care of, this section preps $nextLine and
   * then calls isTooLongInternal() again with the proper formatting.
   */
  if (strlen($nextLine) > count($columnLens)) {
    $nextLine = explode("|", $nextLine);
    $nextLinePrep = array();
    for ($i=0; $i < count($nextLine) - 1; $i++) {
      if ($nextLine[$i] == "") {
        $nextLinePrep[$i][0] = " ";
      } else {
        $nextLinePrep[$i][0] = $nextLine[$i];
      }
    }
    isTooLongInternal($nextLinePrep, $j, $columnLens, "True");
  }
}

/* Function table provides an easy way to display a two-dimensional array in a
 * tableview with a few different formatting options.
 *
 * REQUIRED INPUT $inputArray --------------------------------------------------
 * Input $inputArray is the only required input for table(). This input needs to
 * be in a two-dimensional array format with each sub-level array being the same
 * length (in terms of indexes). The way table() interprets this table is by
 * taking the zeroth index of each array as the top row of the created table.
 * The first index is then the second row, and so on until all indexes have been
 * iterated through. If your array is formatted in the opposite way, having each
 * row being a different array in the top level array, feel free to use
 * reformatArray() down below to reformat your array to be ready to be used with
 * table().
 *
 * OPTIONAL INPUT $barsToggle --------------------------------------------------
 * Input $barsToggle is a one-dimensional array with only two indexes. These two
 * indexes (0, 1) can be either a 0 or a 1 and specify whether or not to print a
 * bar() of "=" across the top or bottom of the table. Index 0 specifies the top
 * bar() and index 1 specifies the bottom bar(). It should be noted that when
 * using this with a table that isn't full width, the bar will not be full width
 * but will be the same width as the shortened table. Both top and bottom bar()
 * are disabled by defualt.
 *
 * OPTIONAL INPUT $fullWidth ---------------------------------------------------
 * Input $fullWidth is an integer equal to either 0 or 1 with a default of 1.
 * This specifies whether or not to attempt to not have a full width table.
 * Meaning, if this is set to 0 and the table elements don't naturally fill or
 * over fill the terminal width, to cut it short so it doesn't inflat the table
 * to fill the full width of the window.
 *
 * EXAMPLE CODE ----------------------------------------------------------------
 * $inputArray = array(
 *   array(
 *     "Item 1 is great at spacing and uses it",
 *     "Item 2 is alright at it and uses it ocassionally",
 *     "Item 3 is very bad at spacing and doesn't use it"
 *   ),
 *   array(
 *     "I Understand Spacing",
 *     "I Like spaces, but don'tAlwaysUseThem",
 *     "WhatAreSpacesAndWhyShouldIUseThem,LikeWhat'sTheValue?"
 *   )
 * );
 *
 * table($inputArray, array(1,1), 1);
 *
 * EXAMPLE CODE OUTPUT (Sample Terminal Window) --------------------------------
  ______________________________________________________________________________
 | =========================================================================== |
 | || Item 1 is great at spacing and    | I Understand Spacing              || |
 | || Item 1 is great at spacing and    | I Understand Spacing              || |
 | || Item 2 is alright at it and uses  | I Like spaces, but                || |
 | ||   it ocassionally                 |   don'tAlwaysUseThem              || |
 | || Item 3 is very bad at spacing     | WhatAreSpacesAndWhyShouldIUseThe- || |
 | ||   and doesn't use it              |   m,LikeWhat'sTheValue?           || |
 | =========================================================================== |
 |_____________________________________________________________________________|
 */
function table($inputArray, $barsToggle = array(0, 0), $fullWidth = 1) {
  // Removes leading and trailing white space from $inputArray
  for ($i=0; $i < count($inputArray); $i++) {
    for ($j=0; $j < count($inputArray[$i]); $j++) {
      // Removes leading white space
      while (substr($inputArray[$i][$j], 0, 1) == " ") {
        $inputArray[$i][$j] = substr($inputArray[$i][$j], 1,
        strlen($inputArray[$i][$j]));
      }
      // Removes trailing white space
      while (substr($inputArray[$i][$j], strlen($inputArray[$i][$j]) - 1,
      strlen($inputArray[$i][$j])) == " ") {
        $inputArray[$i][$j] = substr($inputArray[$i][$j], 0,
        strlen($inputArray[$i][$j]) - 1);
      }
    }
  }

  // Verifies all sub arrays are same length (in indexes)
  $inputArrayLen;
  for ($i=0; $i < count($inputArray); $i++) {
    if ($i == 0) {
      $inputArrayLen = count($inputArray[$i]);
    } elseif (count($inputArray[$i]) != $inputArrayLen) {
      print " ".color("[Fatal error]", "white", "red")
      ." Table view expects all subarrays to be same length, failed on $i\n";
      return;
    }
  }

  // Sets lengths of columns
  $columnLens = array();
  for ($i=0; $i < count($inputArray); $i++) {
    $columnLens[$i] = getMaxLen($inputArray[$i]);
  }

  // Figures out if the length is going to be too big and modifies if necessary
  $termWidth = shell_exec("tput cols");
  /* Uses 6 becuase there of the prefix and suffix endings of the table ('|| '
   * and ' ||') and 3 * (count($inputArray) - 1) because there are three
   * characters (' | ') for every column of the table (count($inputArray) - 1).
   * Then it adds on the sum of all the previously generated $columnLens which
   * then are compared to the width of the terminal and then the $columnLens are
   * shrunk down if necessary
   */
  $totalLen = 6 + (3 * (count($inputArray) - 1)) + array_sum($columnLens);
  while ($totalLen > $termWidth) {
    for ($i=0; $i < count($columnLens); $i++) {
      if ($columnLens[$i] == max($columnLens)) {
        $index = $i;
        $i += count($columnLens) - $i;
      }
    }
    $columnLens[$index]--;
    $totalLen--;
  }

  /* If the full width option is selected and reached by default, this will auto
   * expand the smallest sections until the table width matches the terminal
   * width
   */
  if ($fullWidth == 1) {
    while ($totalLen < $termWidth) {
      for ($i=0; $i < count($columnLens); $i++) {
        if ($columnLens[$i] == min($columnLens)) {
          $index = $i;
          $i += count($columnLens) - $i;
        }
      }
      $columnLens[$index]++;
      $totalLen++;
    }
  }

  // Prints horizontal bar at the top of table if set to do so (off by default)
  if ($barsToggle[0] == 1) {
    bar("=", 0, $totalLen);
  }

  /* This iterates through each row of the table and first checks if any of the
   * elements in that row are too long for the current width of the table with
   * the given column widths. If it is too long it goes through and drops it too
   * new ling with a small indent. Otherwise, it just prints the line across the
   * table row with the proper formatting
   */
  for ($j=0; $j < count($inputArray[0]); $j++) {  // sub-array level
    $nextLine = "";
    $isTooLong = "False";

    // Checks if this row has elements that are too long or not
    for ($k=0; $k < count($inputArray); $k++) {
      if (strlen($inputArray[$k][$j]) > $columnLens[$k]) {
        $isTooLong = "True";
      }
    }

    /* If there aren't any elements that are too long this section is called
     * and then prints out each element with the proper spacing for the row with
     * the vertical bars to help split columns apart.
     */
    if ($isTooLong == "False") {
      print "||";
      for ($k=0; $k < count($inputArray); $k++) {
        print " ";
        $length = strlen($inputArray[$k][$j]);
        while ($length < $columnLens[$k]) {
          $inputArray[$k][$j] .= " ";
          $length++;
        }
        print $inputArray[$k][$j]." |";
      }
      print "|\n";
    }

    /* If there is an element or are elements that are too long this section is
     * called and it evaluates the elemetns of the section individually and
     * prints each element in its' own section taking up mutliple lines if
     * needed.
     */
    if ($isTooLong == "True") {
      isTooLongInternal($inputArray, $j, $columnLens);
    }
  }

  // Prints horizontal bar at bottom of table if set to do so (off by default)
  if ($barsToggle[1] == 1) {
    bar("=", 0, $totalLen);
  }
}

/* Function reformatArray() takes in one input (two-dimensional array) and
 * outputs another two-dimensional array that has been reformatted so that the
 * rows and columns have been swapped. This can be used more than once and works
 * both forward and backwards, however, using twice in a row does nothing to the
 * array. This function works great in conjunction with table() and can be used
 * to help format data to be ready for inputing into a table or making it easier
 * to use before putting it back into a format to be easy to use in a table.
 *
 * EXAMPLE CODE ----------------------------------------------------------------
 * $inputArray = array(
 *   array(
 *     "Item 1 is great at spacing and uses it",
 *     "Item 2 is alright at it and uses it ocassionally",
 *     "Item 3 is very bad at spacing and doesn't use it"
 *   ),
 *   array(
 *     "I Understand Spacing",
 *     "I Like spaces, but don'tAlwaysUseThem",
 *     "WhatAreSpacesAndWhyShouldIUseThem,LikeWhat'sTheValue?"
 *   )
 * );
 *
 * $inputArray2 = array(
 *   array(
 *     "Item 1 is great at spacing and uses it",
 *     "I Understand Spacing"
 *   ),
 *   array(
 *     "Item 2 is alright at it and uses it ocassionally",
 *     "I Like spaces, but don'tAlwaysUseThem"
 *   ),
 *   array(
 *     "Item 3 is very bad at spacing and doesn't use it",
 *     "WhatAreSpacesAndWhyShouldIUseThem,LikeWhat'sTheValue?"
 *   )
 * );
 *
 * table($inputArray, array(1,1), 1);
 * print "\n";
 * table(reformatArray($inputArray2), array(1,1), 1);
 *
 * EXAMPLE CODE OUTPUT (Sample Terminal Window) --------------------------------
  ______________________________________________________________________________
 | =========================================================================== |
 | || Item 1 is great at spacing and    | I Understand Spacing              || |
 | || Item 1 is great at spacing and    | I Understand Spacing              || |
 | || Item 2 is alright at it and uses  | I Like spaces, but                || |
 | ||   it ocassionally                 |   don'tAlwaysUseThem              || |
 | || Item 3 is very bad at spacing     | WhatAreSpacesAndWhyShouldIUseThe- || |
 | ||   and doesn't use it              |   m,LikeWhat'sTheValue?           || |
 | =========================================================================== |
 |                                                                             |
 | =========================================================================== |
 | || Item 1 is great at spacing and    | I Understand Spacing              || |
 | || Item 1 is great at spacing and    | I Understand Spacing              || |
 | || Item 2 is alright at it and uses  | I Like spaces, but                || |
 | ||   it ocassionally                 |   don'tAlwaysUseThem              || |
 | || Item 3 is very bad at spacing     | WhatAreSpacesAndWhyShouldIUseThe- || |
 | ||   and doesn't use it              |   m,LikeWhat'sTheValue?           || |
 | =========================================================================== |
 |_____________________________________________________________________________|
 */
function reformatArray($inputArray) {
  // Validates that top level input is actually an array
  if (gettype($inputArray) != "array") {
    print " ".color("[Fatal error]", "white", "red").
    " Array needed for input\n";
    return;
  }

  /* Iterates through every element of $inputArray and validates it against a
   * number of factors
   */
  $cols = 0;
  for ($i=0; $i < count($inputArray); $i++) {
    // Validates that each element of the array is in fact another array
    if (gettype($inputArray[$i]) != "array") {
      print " ".color("[Fatal error]", "white", "red").
      " Two-Dimensional array expected\n";
      return;
    }

    // Validates that each element of the array is the same length
    if ($i == 0) {
      $cols = count($inputArray[$i]);
    } elseif ($cols !== count($inputArray[$i])) {
      print " ".color("[Fatal error]", "white", "red").
      " All Array elements need to be the same length [$i]\n";
      return;
    }

    // Validates that each element of the sub array isn't another array
    for ($j=0; $j < count($inputArray[$i]); $j++) {
      if (gettype($inputArray[$i][$j]) == "array") {
        print " ".color("[Fatal error]", "white", "red").
        " Two-Dimensional array expected\n";
        return;
      }
    }
  }

  // Reformats the given array into the opposite format
  $newArray = array();
  for ($i=0; $i < count($inputArray); $i++) { // out
    for ($j=0; $j < count($inputArray[$i]); $j++) { // in
      $newArray[$j][$i] = $inputArray[$i][$j];
    }
  }

  return $newArray;
}
 ?>
