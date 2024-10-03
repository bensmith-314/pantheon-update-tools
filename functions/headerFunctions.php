<?php

require_once __DIR__ . "/utilityFunctions.php";
require_once __DIR__ . "/textAlignmentFuncitons.php";
require_once __DIR__ . "/tableFunctions.php";

/* Function headers offers a lot of flexibility to build unique headers that
 * allows for the creation of headers with as many sections as needed with many
 * formatting options.
 *
 * REQUIRED INPUT $displayText -------------------------------------------------
 * Input $displayText is the primary place where data is entered into headers()
 * $displayText has some strict guidelines for how the data needs to be
 * formatted in order to be functional in all of its' different states.
 * At its' core, $displayText is a two dimensional array that gives way to being
 * able to create any number of sections based on the number of arrays that live
 * within the top level array. It should be noted that for every section array
 * that exists, there needs to be an associated $textFormat array to tell
 * headers() how to display that content (see example code for better
 * explanation). For all formatted views other than tableview (see below), the
 * proces for setting up the data to be displayed is quite easy. For each
 * section all that needs to happen is a new array needs to be added into
 * $displayText. The data in that array can be anything that can be printed on
 * one line (strings work best, but integers and other data types should work as
 * well but do not put additional arrays of data though). These arrays of data
 * can be of whatever length desired, but must contain at least one element or
 * else the table will fail to build.
 *
 * REQUIRED INPUT $textFormat --------------------------------------------------
 * Other than with the tableview, all sections can be left justified, centered,
 * or right justified independently of each other. Note: use 0 with tableview
 * Specified in $textFormat[0] (part of second parameter (required))
 * 0 = Left Justified Text
 * 1 = Centered Text
 * 2 = Right Justified Text
 *
 * There are many prefixing options in built, no prefix, numbered, bulleted, and
 * if needed, custom prefixing is available.
 * Specified in $textFormat[1] (part of second parameter (required))
 * 0 = No Prefixing
 * 1 = Numbered List
 * 2 = Bulleted list (each item prefixed with '* ')
 * 3 = Custom Prefix (needs to be specified in $textFormat[2] of same array)
 * 4 = tableView (see below)
 *
 * As alluded to above, $textFormat[2] is only needed if the preceding element
 * $textFormat[1] == 3 in which case the table will default to useing the
 * bulleted list.
 *
 * tableview ADDTIONAL INFO ----------------------------------------------------
 * As specified above, when using the tableview format, the $textFormat array
 * for the view should look like array(0,4). When formating the data for use in
 * tableview, it should be stored within a two dimensional array that is then
 * put into its' own section within $displayText. Note that this is the only
 * time in which putting a two dimensional array within $displayText (resulting
 * in a three dimensional array) is permissable
 *
 * OPTIONAL INPUT $customBars --------------------------------------------------
 * There are also options to customize the horizontal bars that start, finish
 * and split the sections. The default is to have a horizontal bar of '=' across
 * the top and bottom of the header with a horizontal bar of an em dash
 * (\u{2014}) splitting the sections.
 * Specified in $customBars (third parameter (optional))
 * $customBars[0] = Top horizontal bar character (default '=')
 * $customBars[1] = Section splitter horizontal bar character (default \u{2014})
 * $customBars[2] = Bottom horizontal bar character (default '=')
 *
 * EXAMPLE CODE ----------------------------------------------------------------
 * $displayText = array(
 *  array("Introductory", "Header", "Array"),
 *  array("This array has", "Similar substance,", "But...", "Extra lines!"),
 *  array("This is the last non-tableview", "Array with two lines"),
 *  array(
 *   array("Item 1 is great", "Item 2 isn't", "Item 3 is best"),
 *   array("[Done]", "[In Progress]", "[Scheduled]"),
 *   array("This is ok", "This is hard", "This is complicated")
 *  )
 * );
 *
 * $textFormat = array(
 *   array(1,0),        // Centered text with no prefix
 *   array(0,1),        // Left Justified text numbered
 *   array(2,3,"-- "),  // Right Justified text with custom prefix "-- "
 *   array(0,4)         // Tableview
 * );
 *
 * $customBars = array("=", "-", "=");
 *
 * headers($displayText, $textFormat, $customBars);
 *
 * EXAMPLE CODE OUTPUT (Sample Terminal Window) --------------------------------
  ______________________________________________________________________________
 | =========================================================================== |
 |                                Introductory                                 |
 |                                   Header                                    |
 |                                   Array                                     |
 | --------------------------------------------------------------------------- |
 | 1. This array has                                                           |
 | 2. Similar substance,                                                       |
 | 3. But...                                                                   |
 | 4. Extra lines!                                                             |
 | --------------------------------------------------------------------------- |
 |                                           -- This is the last non-tableview |
 |                                                     -- Array with two lines |
 | --------------------------------------------------------------------------- |
 | || Item 1 is great       | [Done]                | This is ok            || |
 | || Item 2 isn't          | [In Progress]         | This is hard          || |
 | || Item 3 is best        | [Scheduled]           | This is complicated   || |
 | =========================================================================== |
 |_____________________________________________________________________________|
 */
function headers(
  $displayText, $textFormat, $customBars = array("=", "\u{2014}", "=")) {
  // Makes sure that both $displayText and $textFormat are arrays
  if (gettype($displayText) != "array") {
    print " ".color("[Fatal error]", "white", "red")." \$displayText needs to ".
    "be an array\n";
    return;
  } elseif (gettype($textFormat) != "array") {
    print " ".color("[Fatal error]", "white", "red")." \$textFormat needs to ".
    "be an array\n";
    return;
  }

  // Verifies that both $displayText and $textFormat are the same size
  if (count($displayText) != count($textFormat)) {
    print " ".color("[Fatal error]", "white", "red")
      ." \$displayText and \$textFormat need to be same length\n";
    return;
  }
//------------------------------------------------------------------------------
  /* This takes care of the validation of the $displayText input
   * It takes a look at the overall structure of $displayText and makes sure
   * that all of the required components are in place and formatted correctly
   */
  // Iterate through all indexes of $displayText
  for ($i=0; $i < count($displayText); $i++) {
    // Checks to see if index is an array and NOT empty
    if (gettype($displayText[$i]) == "array" && count($displayText[$i]) != 0) {
      /* Knowing that $displayText[$i] is a filled array, it is now important to
       * validate the indexes in this array to make sure that if it contains
       * additional arrays (for table view) that they are correlated correctly
       * with the appropriate $textFormat entry (needs to be set to 4).
       */
      for ($j=0; $j < count($displayText[$i]); $j++) {
        /* If it isn't an array but is set be formatted as such, this will
         * return an error.
         */
        if (gettype($displayText[$i][$j]) != "array" &&
        $textFormat[$i][1] == 4) {
          print " ".color("[Fatal error]", "white", "red")
            ." To use tableview, all sub arrays need to be a 2D array\n";
          return;
        // If it is an array, but isn't set to 4 this will also return an error
        } elseif (gettype($displayText[$i][$j]) == "array" &&
        $textFormat[$i][1] != 4) {
          print " ".color("[Fatal error]", "white", "red").
          " In order to use tableview, \$textFormat[$i] needs to be set to 4\n";
          return;
        }
      }

    // Checks to see if index is an array, but IS empty (error)
    } elseif (gettype($displayText[$i]) == "array" &&
    count($displayText[$i]) == 0) {
      print " ".color("[Fatal error]", "white", "red")
        ." \$displayText[$i] can't be empty\n";
        return;

    /* If index isn't an array, this returns an error, because the code moving
     * forward expects all printable text to exist as a second level of
     * $displayText
     */
    } else {
      print " ".color("[Fatal error]", "white", "red")
        ." \$displayText: 2D array expected\n";
      return;
    }
  }
//------------------------------------------------------------------------------
  /* This takes care of the validation of the $textFormat input
   * It takes a look at the overall structure of $textFormat and makes sure
   * that all of the required components are in place and formatted correctly
   */
  // Iterate through all indexs of $textFormat
  for ($i=0; $i < count($textFormat); $i++) {
    // Checks to see if index is an array and NOT empty
    if (gettype($textFormat[$i]) == "array" && count($textFormat[$i]) != 0) {
      // Checks to see if those arrays have more than 3 indexes, max needed
      if (count($textFormat[$i]) > 3) {
        print " ".color("[Warning]", "white", "green")
          ." Input \$textFormat only expects up to three values";
      }
      // Checks to make sure all array indexes (but last) are integers
      for ($j=0; $j < 2; $j++) {
        if (gettype($textFormat[$i][$j]) != "integer") {
          print " ".color("[Fatal error]", "white", "red")." Input \""
          .$textFormat[$i][$j]."\" [$i, $j] needs to be an integer\n";
          return;
        }
      }

      /* Checks to see if text-justification format is within the proper bounds
       * of 0 and 2. This justification is what tells the script which
       * justification to use.
       */
      if ($textFormat[$i][0] < 0 || $textFormat[$i][0] > 2) {
        print " ".color("[Fatal error]", "white", "red")." Input \""
        .$textFormat[$i][0]."\" [$i, 0] needs to be between 0 and 2\n";
        return;
      /* Checks to see if prefix-selector format is within the proper bounds of
       * 0 and 4. This selector tells the script which prefix to use or if 4 is
       * selected, tells the script to display that section as a tableview
       */
      } elseif ($textFormat[$i][1] < 0 || $textFormat[$i][1] > 4) {
        print " ".color("[Fatal error]", "white", "red")." Input \""
        .$textFormat[$i][1]."\" [$i, 1] needs to be between 0 and 4\n";
        return;
      }

      /* Checks to see if $textFormat specifiies a custom prefix and if it then
       * actually provides a custom prefix. If it doesn't, it reverts it to the
       * default prefix that is built in with the function.
       */
      if ($textFormat[$i][1] == 3 && count($textFormat[$i]) != 3) {
        $textFormat[$i][1] = 2;
      }

    // Checks to see if index is an array but IS empty (error)
    } elseif (gettype($textFormat[$i]) == "array" &&
    count($textFormat[$i]) == 0) {
      print " ".color("[Fatal error]", "white", "red")
      ." \$textFormat[$i] can't be empty\n";
      return;
      /* If index isn't an array, this returns an error, because the code moving
       * forward expects all text formats to exist as a second level of
       * $textFormat
       */
    } else {
      print " ".color("[Fatal error]", "white", "red")
        ." \$textFormat: 2D array expected\n";
      return;
    }
  }
//------------------------------------------------------------------------------

  // Checks if the $customBars array is of the correct length
  if (count($customBars) != 3) {
    print " ".color("[Fatal error]", "white", "red")
      ." \$customBars expects 3 elements\n";
    return;
  }

  /* Clears the current terminal screen and prints the first horizontal bar
   * as specified in $customBars[0]
   */
  system("eval clear -x");
  bar($customBars[0]);

  /* This iterates through each $displayText index and displays them to the
   * console with the correct formatting as specified in $textFormat.
   */
  for ($i=0; $i < count($displayText); $i++) {

    // Adds Numbered Prefix to $displayText
    if ($textFormat[$i][1] == 1) {
      for ($j=0; $j < count($displayText[$i]); $j++) {
        $displayText[$i][$j] = ($j + 1).". ".$displayText[$i][$j];
      }
    }
    // Adds Bullet List Prefix to $displayText
    elseif ($textFormat[$i][1] == 2) {
      for ($j=0; $j < count($displayText[$i]); $j++) {
        $displayText[$i][$j] = "* ".$displayText[$i][$j];
      }
    }
    // Adds Custom Prefix to $displayText
    elseif ($textFormat[$i][1] == 3) {
      for ($j=0; $j < count($displayText[$i]); $j++) {
        $displayText[$i][$j] = $textFormat[$i][2].$displayText[$i][$j];
      }
    }

    /* First checks to see if tableview has been selected and if it hasn't, it
     * then prints each element with the proper additional formatting
     */
    if ($textFormat[$i][1] != 4) {
      // Prints with no additional formatting as left justified text
      if ($textFormat[$i][0] == 0) {
        for ($j=0; $j < count($displayText[$i]); $j++) {
          print $displayText[$i][$j]."\n";
        }
      // Prints with center justified text
      } elseif ($textFormat[$i][0] == 1) {
        centerString($displayText[$i]);
      // Prints with right justified text
      } elseif ($textFormat[$i][0] == 2) {
        rightString($displayText[$i]);
      }
    /* Prints in tableview format using the table() function from
     * tableFunctions.php
     */
    } elseif ($textFormat[$i][1] == 4) {
      table($displayText[$i]);
    }

    /* Checks to see if the remaining sections are greater than 0 and if so,
     * it prints the section splitter horizontal bar as specified in
     * $customBars[1]
     */
    if ($i < count($displayText) - 1) {
      bar($customBars[1]);
    }
  }

  // Prints the final closing horizontal bar as specified in $customBars[2]
  bar($customBars[2]);
}
 ?>
