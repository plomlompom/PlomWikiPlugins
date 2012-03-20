<?php
# PlomWiki plugin: StandardLib. Functions expected by other plugins.
# 
# Copyright 2010-2012 Christian Heller / <http://www.plomlompom.de/>
# License: AGPLv3 or any later version. See file LICENSE for details.

function GetAllPageTitles() {
# Return an array of all of the PlomWiki page's titles.
  global $pages_dir, $legal_title; 
  $p_dir = opendir($pages_dir);
  $titles = array();
  while (FALSE !== ($fn = readdir($p_dir)))
    if (    is_file($pages_dir.$fn)
        and preg_match('/^'.$legal_title.'$/', $fn))
      $titles[] = $fn;
  closedir($p_dir); 
  return $titles; }
