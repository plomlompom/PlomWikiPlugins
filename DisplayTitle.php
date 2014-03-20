<?php
# PlomWiki plugin: DisplayTitle. Set display-specific alternate titles.
# 
# Copyright 2014 Christian Heller / <http://www.plomlompom.de/>
# License: AGPLv3 or any later version. See file LICENSE for details.

$s = ReadStringsFile($plugin_strings_dir.'DisplayTitle', $s);
$s['ActionLinks_page_Plugins'] .= $s['DisplayTitle_page_ActionLinks'];
$DisplayTitle_dir = $plugin_dir.'DisplayTitle/';
$DisplayTitle_path_DB = $plugin_dir.'DisplayTitle/titles';
$DisplayTitle_DB_separator = ':';

# Apply display title to "page_view" action.
$hook_OutputHTML .= '
global $action, $page_title;
if ($action == \'Action_page_view\')
  $s[\'title\'] = DisplayTitle_get($s["page_title"]);
';

function Action_page_SetDisplayTitle() {
# Form for setting the display title via PrepareWrite_DisplayTitle().
  global $s, $title;
  $s['DisplayTitle_Default'] = DisplayTitle_get($title);
  $s['title']   = $s['Action_page_DisplayTitle:title'];
  $s['content'] = $s['Action_page_DisplayTitle:form'];
  OutputHTML(); }

function DisplayTitle_getDB() {
# Return display title DB in an easily readable form.	
  global $DisplayTitle_path_DB, $DisplayTitle_DB_separator, $nl;
  if (is_file($DisplayTitle_path_DB)) {
    $lines = explode($nl, file_get_contents($DisplayTitle_path_DB));
    foreach ($lines as $line) {
      $both_titles = explode($DisplayTitle_DB_separator, $line);
      $entries[$both_titles[0]] = $both_titles[1]; }
    unset($entries['']); } # handle last $nl-line
  return $entries; }

function DisplayTitle_get($pagetitle) {
# Return display title for $pagetitle, or $pagetitle if there is none.
  $entries = DisplayTitle_getDB();
  if ($entries[$pagetitle])
    return $entries[$pagetitle];
  return $pagetitle; }

function DisplayTitle_set($page_title, $tmp_display_title, $tmp) {
# Set $tmp_display_title content as display title for $page_title. Uses,
# expects and finally deletes file at $tmp as temporary file. 
  global $DisplayTitle_dir, $DisplayTitle_path_DB,
	     $DisplayTitle_DB_separator, $nl;
  if (!is_file($tmp))
    return;
  if (!is_dir($DisplayTitle_dir))
    mkdir($DisplayTitle_dir);
  $entries = DisplayTitle_getDB();
  $display_title = file_get_contents($tmp_display_title);
  if ($display_title == $page_title)
    unset($entries[$page_title]);
  else
    $entries[$page_title] = $display_title;
  foreach ($entries as $page_title => $display_title)
    $db_text .= $page_title.$DisplayTitle_DB_separator.$display_title.
                                                                    $nl;
  file_put_contents($tmp, $db_text);
  rename($tmp, $DisplayTitle_path_DB);
  unlink($tmp_display_title); }

function PrepareWrite_DisplayTitle(&$redir) {
# Return todo list for setting display title for page named $title.
  global $nl, $s, $title;
  $redir = $s['title_url'];
  $display_title = Sanitize($_POST['DisplayTitle']);
  $display_title = EscapeHTML($display_title);
  $display_title = str_replace($nl, ' ', $display_title);
  if (!$display_title)
    ErrorFail('PrepareWrite_Blog_Article():NoTitle');
  $tmp_display_title = NewTemp($display_title);
  $tmp = NewTemp();
  return 'DisplayTitle_set("'.$title.'", "'.$tmp_display_title.'", "'.
                                                       $tmp.'");'.$nl; }
