<?php

/* Function centerString takes in a string or array input and outputs array or
 * a string depending on which is inputed (string -> string, array -> array)
 * Input value is taken in and modified with leading white space to produce
 * a string or array of strings which are centered on the current terminal
 * based on its current width. Each string in a given array is uniquely centered
 */
// NOTE: Do not call centerStringInternal(), its only for internal usage
function centerStringInternal($displayText) {
 $i = 0;
 $width = shell_exec("tput cols");
 settype($width, "integer");
 $wordlen = strlen($displayText);
 $fwidth = (($width - $wordlen) / 2) - 1;

 while ($i < $fwidth) {
   print " ";
   $i++;
 }
 print "$displayText\n";
}

function centerString($displayText) {
  if (gettype($displayText) == "array") {
    foreach ($displayText as $word) {
      centerStringInternal($word);
    }
  } elseif (gettype($displayText) == "string") {
    centerStringInternal($displayText);
  } else {
    print "Notice: \$displayText only accepts strings and arrays\n";
  }
}

/* Function rightString takes in a string or array input and outputs array or
 * a string depending on which is inputed (string -> string, array -> array)
 * Input value is taken in and modified with leading white space to produce
 * a string or array of strings which are right justified on the current
 * terminal based on its current width. Each string in a given array is uniquely
 * justified to the right of the terminal window
*/
// NOTE: Do not call rightStringInternal(), its only for internal usage
function rightStringInternal($displayText) {
  $i = 0;
  $width = shell_exec("tput cols");
  settype($width, "integer");
  $wordlen = strlen($displayText);
  $fwidth = $width - $wordlen;

  while ($i < $fwidth) {
    print " ";
    $i++;
  }
  print "$displayText\n";
}

function rightString($displayText) {
  if (gettype($displayText) == "array") {
    foreach ($displayText as $word) {
      rightStringInternal($word);
    }
  } elseif (gettype($displayText) == "string") {
    rightStringInternal($displayText);
  } else {
    print "Notice: \$displayText only accepts strings and arrays\n";
  }
}

 ?>
