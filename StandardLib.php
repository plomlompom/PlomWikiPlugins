<?php

function GetAllPageTitles()
# Return an array of all of the PlomWiki page's titles.
{ global $pages_dir, $legal_title; 
  $p_dir = opendir($pages_dir);
  $titles = array();
  while (FALSE !== ($fn = readdir($p_dir)))
    if (is_file($pages_dir.$fn) and preg_match('/^'.$legal_title.'$/', $fn))
      $titles[] = $fn;
  closedir($p_dir); 
  return $titles; }

function BuildPostForm($URL, $input, $ask_pw = NULL, $class = NULL)
# HTML form. $URL = action, $input = code between, $ask_pw = PW input element.
{ global $esc, $nl;
  if ($ask_pw === NULL)
    $ask_pw = 'Admin '.$esc.'pw'.$esc.': <input name="pw" type="password" />'.
                              '<input name="auth" type="hidden" value="*" />';
  if ($class !== NULL)
    $class = 'class="'.$class.'" ';
  return '<form '.$class.'method="post" action="'.$URL.'">'.$nl.$input.$nl.
                                                                    $ask_pw.$nl.
         '<input type="submit" value="OK" />'.$nl.'</form>'; }

function SafeWrite($path_original, $path_temp)
# Avoid data corruption: Exit if no temp file. Rename, don't overwrite directly.
{ if (!is_file($path_temp))    return;
  if (is_file($path_original)) unlink($path_original); 
  rename($path_temp, $path_original); }
