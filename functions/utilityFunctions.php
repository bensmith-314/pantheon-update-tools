<?php

/* Function color takes in three string inputs and returns a colored string
 * Input $text is a string to be modified
 * Input $foreground is a color for $text to be changed to, must exist in the
 * the list of colors provided in the function or else it defaults to light grey
 * Input $background defaults to no background color, but can be modified to
 * be any of the listed colors, but if an incorrect value is provided, the color
 * defaults back to no background color
 */
// BUG: In the future needs to validate against escape codes such as \n in $text
function color($text, $foreground, $background = "") {
  // Keyed array of all colors that the text can be
  $foregroundColor = array(
    "black" => "0;30", "dark grey" => "1;30", "red" => "0;31",
    "light red" => "1;31", "green" => "0;32", "light green" => "1;32",
    "brown" => "0;33", "yellow" => "1;33", "blue" => "0;34",
    "light blue" => "1;34", "magenta" => "0;35", "light magenta" => "1;35",
    "cyan" => "0;36", "light cyan" => "1;36", "light grey" => "0;37",
    "white" => "1;37"
  );
  // Keyed array of all colors that the printed text's background can be
  $backgroundColor = array(
    "black" => ";40m", "red" => ";41m", "green" => ";42m", "yellow" => ";43m",
    "blue" => ";44m", "magenta" => ";45m", "cyan" => ";46m",
    "light grey" => ";47m"
  );
  // Closing ANSI code to revert foreground and background to normal colors
  $killColors = "\033[0m";

  // Checks if $background is empty and $foreground is valid
  if (strlen($background) == 0 && array_key_exists($foreground,
  $foregroundColor)) {
    $text = "\033[".$foregroundColor[$foreground]."m".$text.$killColors;
  }
  // Checks if $foreground is valid and $background is valid
  elseif (array_key_exists($foreground, $foregroundColor) &&
  array_key_exists($background, $backgroundColor)) {
    $text = "\033[".$foregroundColor[$foreground].$backgroundColor[$background].
      $text.$killColors;
  }
  // Checks if $foreground not valid and $background empty
  elseif (array_key_exists($foreground, $foregroundColor) == False &&
  strlen($background) == 0) {
    $text = "\033[".$foregroundColor["light grey"]."m".$text.$killColors;
  }
  // Checks if $foreground valid and $background invalid
  elseif (array_key_exists($foreground, $foregroundColor) &&
  array_key_exists($background, $backgroundColor) == False) {
    $text = "\033[".$foregroundColor[$foreground]."m".$text.$killColors;
  }
  // Checks if $foreground not valid and $background valid
  elseif (array_key_exists($foreground, $foregroundColor) == False &&
  array_key_exists($background, $backgroundColor)) {
    $text = "\033[".$foregroundColor["light grey"].$backgroundColor[$background].
      $text.$killColors;
  }
  return $text;
}

/* Function bar takes in two inputs and outputs a printed line
 * Input $barChar needs to be a single character length string (char) or can be
 * a unicode character such as the em dash '\u{2014}'
 * This input will be printed across the current width of the terminal
 * window and ends with a newline character.
 * Input $customWidth is an optional input to specify the width of the printed
 * $barChar, this is utilitized in tableFunctions.php and gives good utility to
 * the function beyond terminal width printing
 */
// BUG: Length validation has been disabled to support using unicode characters
function bar($barChar, $newLine = 1, $customWidth = 0) {
  // Get current terminal width
  $termWidth = shell_exec("tput cols");

  /* Verify that $barChar is only 1 char long and errors out if not
   * If only 1 char long, then it prints $barChar for specified or unspecified
   * width. Either way it ends with a necessary new line after error or $barChar
   * printing on the screen.
   */
//if (strlen($barChar) == 1) {
    if ($customWidth > 0) {$width = $customWidth;} else {$width = $termWidth;}
      for ($i=0; $i < $width; $i++) {
        print $barChar;
      }
//} elseif (strlen($barChar) > 1) {}
  // } else {
  //   print " ".color("[Warning]", "white", "green")." \$barChar only accepts "
  //   ."character inputs with length of 1";
  // }
  if ($newLine == 1) {
    print "\n";
  }
}
 ?>
