<?php
/** This file builds a number of functions that are used in a semi-recursively
 * to take a list of names and dates and organize them from oldest to newest
 * dates (oldest/never updated at the front and newest/latest dates at the
 * front). The dates that are sorted off of need to be in YYYY-MM-DDThh-mm-ss.
 */

/** This and all of the following compare*() functions take in two inputs of the
 * dates that need to be compared. These do one of three things, if the second
 * date is smaller (i.e. newer or more recent) a 0 is returned, if the second is
 * bigger (i.e. older or less recent) a 1 is returned, and if the first and
 * second dates are the same for the current date comparison it sends it to the
 * next largest sub function (year to month, month to day etc.) until the
 * compareSecond() function in which case if the two dates are completely
 * identical it likewise returns a 1 (which places the next item after the
 * current one).
 */
// Compares the seconds of two dates
function compareSecond($firstDate, $secondDate) {
  // If newer second
  if (substr($firstDate, 17, 2) < substr($secondDate, 17, 2)) {
    return 0;
  // If older second
  } elseif (substr($firstDate, 17, 2) > substr($secondDate, 17, 2)) {
    return 1;
  // If the same second (returns as if older second)
  } else {
    return 1;
  }
}

// Compares the minutes of two dates
function compareMinute($firstDate, $secondDate) {
  // If newer minute
  if (substr($firstDate, 14, 2) < substr($secondDate, 14, 2)) {
    return 0;
  // If older minute
  } elseif (substr($firstDate, 14, 2) > substr($secondDate, 14, 2)) {
    return 1;
  // If the same minute
  } else {
    if (compareSecond($firstDate, $secondDate) == 0) {
      return 0;
    } else {
      return 1;
    }
  }
}

// Compares the hours of two dates
function compareHour($firstDate, $secondDate) {
  // If newer hour
  if (substr($firstDate, 11, 2) < substr($secondDate, 11, 2)) {
    return 0;
  // If older hour
  } elseif (substr($firstDate, 11, 2) > substr($secondDate, 11, 2)) {
    return 1;
  } else {
  // If the same hour
    if (compareMinute($firstDate, $secondDate) == 0) {
      return 0;
    } else {
      return 1;
    }
  }
}

// Compares the days of two dates
function compareDay($firstDate, $secondDate) {
  // If newer day
  if (substr($firstDate, 8, 2) < substr($secondDate, 8, 2)) {
    return 0;
  // If older day
  } elseif (substr($firstDate, 8, 2) > substr($secondDate, 8, 2)) {
    return 1;
  // If the same day
  } else {
    if (compareHour($firstDate, $secondDate) == 0) {
      return 0;
    } else {
      return 1;
    }
  }
}

// Compares the months of two dates
function compareMonth($firstDate, $secondDate) {
  // If newer month
  if (substr($firstDate, 5, 2) < substr($secondDate, 5, 2)) {
    return 0;
  // If older month
  } elseif (substr($firstDate, 5, 2) > substr($secondDate, 5, 2)) {
    return 1;
  // If the same month
  } else {
    if (compareDay($firstDate, $secondDate) == 0) {
      return 0;
    } else {
      return 1;
    }
  }
}

// Compares the years of two dates
function compareYear($firstDate, $secondDate) {
  // If newer year
  if (substr($firstDate, 0, 4) < substr($secondDate, 0, 4)) {
    return 0;
  // If older year
  } elseif (substr($firstDate, 0, 4) > substr($secondDate, 0, 4)) {
    return 1;
  // If the same year
  } else {
    if (compareMonth($firstDate, $secondDate) == 0) {
      return 0;
    } else {
      return 1;
    }
  }
}

/** This function utilizes the semi-recursive set of functions and takes in two
 * arrays. The first one being the names of the dates and the second being the
 * actual dates themselves. It should be noted that all of the operations done
 * on the $dateArray are also done on the $nameArray.
*/
function dateDiff($nameArray, $dateArray) {
  $noDate = array();
  $noDateName = array();
  $isDate = array();
  $isDateName = array();
  $finalDates = array();
  $finalNames = array();

  // Verifies that both $nameArray and $dateArray are the same size
  if (count($nameArray) != count($dateArray)) {
    print " ".color("[Fatal error]", "white", "red")
      ." \$nameArray and \$dateArray need to be same length\n";
    return;
  }

  /** Get all the 0000 dates out and pushed into it's own mini array that will
   * be merged back with the main list after it gets sorted. These are stored
   * in $noDate and $noDateName
   */
  for ($i = count($dateArray) - 1; $i > 0; $i--) {
    if ($dateArray[$i] == "0000-00-00T00:00:00") {
      array_push($noDate, $dateArray[$i]);
      array_push($noDateName, $nameArray[$i]);
      array_splice($dateArray, $i, 1);
      array_splice($nameArray, $i, 1);
    }
  }

  // Order all other dates
  for ($i=0; $i < count($dateArray); $i++) {
    /** If the this is the first item, this pushes the first items into their
     * respective arrays.
     */
    if ($i == 0) {
      array_push($isDate, $dateArray[$i]);
      array_push($isDateName, $nameArray[$i]);
    // If this isn't the first item, runs through some sorting checks here.
    } else {
      /** This runs the semi-recursive functions from above and sees if the
       * output returned is a 1 (older) or a 0 (newer). If a 1 is returned the
       * item is shoved to the front
       */
      if (compareYear($isDate[0], $dateArray[$i]) == 1) {
        array_unshift($isDate, $dateArray[$i]);
        array_unshift($isDateName, $nameArray[$i]);
      /** If the site isn't newer than the first item, this checks against all
       * all items in the list one by one and sees if they are newer or older
       * than the item currently being sorted. The following while statement
       * runs until it finds the first item that is newer instead of older in
       * the current list. It then creates a temporary array that everything
       * after the after is then pushed into. It then cuts the array up, puts
       * the current item in, and splices the array back together. This is
       * repeated for each item in the list until it is fully sorted.
       */
      } else {
        $j = 0;
        while ($j < (count($isDate)) &&
        compareYear($isDate[$j], $dateArray[$i]) == 0) {
          $j++;
        }
        $tempDate = array();
        $tempName = array();
        for ($k=$j; $k < count($isDate); $k++) {
          array_push($tempDate, $isDate[$k]);
          array_push($tempName, $isDateName[$k]);
        }
        array_unshift($tempDate, $dateArray[$i]);
        array_unshift($tempName, $nameArray[$i]);
        array_splice($isDate, $j, count($isDate), $tempDate);
        array_splice($isDateName, $j, count($isDateName), $tempName);
      }
    }
  }

  /** This pushes $noDate and $noDateName into the final array. These are done
   * first since they have no date to be placed in correctly in the array.
   */
  for ($i=0; $i < count($noDate); $i++) {
    array_push($finalDates, $noDate[$i]);
    array_push($finalNames, $noDateName[$i]);
  }

  /** This pushes $isDate and $isDateName into the final array as well. This
   * happens second for reasons in previous comment.
   */
  for ($i=0; $i < count($isDate); $i++) {
    array_push($finalDates, $isDate[$i]);
    array_push($finalNames, $isDateName[$i]);
  }

  /** This is the summary portion of the function. This starts with a graph of
   * the dates with their relative frequency graphed out.
   */
  print "Graph of Updates Over Time\n";
  $count = 0;     // Local count of how many items per date
  $dateCount = 0; // How many dates total
  for ($i=0; $i < count($finalDates); $i++) {
    // If first item prints out the date
    if ($i == 0) {
      print substr($finalDates[$i], 0, 10);
      $count++;
      $dateCount++;
    // If the date is the same as the previous, increment the count.
    } elseif (substr($finalDates[$i], 0, 10) ==
    substr($finalDates[$i - 1], 0, 10)) {
      $count++;
    /** When the item is different from the one prior, it then prints out the
     * count and sets it up for the next items date.
     */
    } else {
      if ($count < 10) {
        print " [$count]  | ";
      } else {
        print " [$count] | ";
      }
      bar("x", 0, $count);
      print "\n";
      print substr($finalDates[$i], 0, 10);
      $count = 1;
      $dateCount++;
    }
  }
  // This prints out the final line of the graph.
  if ($count < 10) {
    print " [$count]  | ";
  } else {
    print " [$count] | ";
  }
  print bar("x", 0, $count)."\n\n";

  /** This is a printout of some statistics of items that are now available for
   * use in the list. It uses some aggressive comparison to get the oldest site
   * updated and gives a few other stats that are nice to look at.
   */
  print "Number of sites available for updates: ".count($finalNames)."\n";
  print "Oldest site is: $finalNames[0] - Last updated on $finalDates[0]\n";
  print "Oldest site was updated ";
  $now = date("Y-m-d");
  if (substr($now, 0, 4) - substr($finalDates[0], 0, 4) > 0) {
    print (substr($now, 0, 4) - substr($finalDates[0], 0, 4))." years";
  }
  if (substr($now, 0, 4) - substr($finalDates[0], 0, 4) > 0 &&
  substr($now, 5, 2) - substr($finalDates[0], 5, 2) > 0 &&
  substr($now, 8, 2) - substr($finalDates[0], 8, 2) > 0) {
    print ", ";
  } elseif (substr($now, 0, 4) - substr($finalDates[0], 0, 4) > 0 &&
  substr($now, 5, 2) - substr($finalDates[0], 5, 2) > 0 &&
  substr($now, 8, 2) - substr($finalDates[0], 8, 2) == 0) {
      print "and ";
    }
  if (substr($now, 5, 2) - substr($finalDates[0], 5, 2) > 0) {
    print substr($now, 5, 2) - substr($finalDates[0], 5, 2)." months";
  }
  if (substr($now, 0, 4) - substr($finalDates[0], 0, 4) > 0 &&
  substr($now, 5, 2) - substr($finalDates[0], 5, 2) > 0 &&
  substr($now, 8, 2) - substr($finalDates[0], 8, 2) > 0) {
    print ", and ";
  } elseif ((substr($now, 0, 4) - substr($finalDates[0], 0, 4) > 0 ||
  substr($now, 5, 2) - substr($finalDates[0], 5, 2) > 0) &&
  substr($now, 8, 2) - substr($finalDates[0], 8, 2) > 0) {
    print " and ";
  }
  if (substr($now, 8, 2) - substr($finalDates[0], 8, 2) > 0) {
    print substr($now, 8, 2) - substr($finalDates[0], 8, 2)." days";
  }
  print " ago\n";
  print "Average amount of sites updated per day: ";
  print round(count($finalDates) / $dateCount)."\n";
  print "At the current rate it takes ~".$dateCount;
  print " days to update every site.\n";
  print "Or it would take ~".ceil($dateCount / 5);
  print " weeks to update every site\n";

  /** Returns the $finalNames to be written into a text file that can be
   * accessed at a later time.
   */
  return $finalNames;
}

 ?>
