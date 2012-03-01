<?php
# PlomWiki plugin: Search
# 
# Provides a very simple case-insensitive full-text string search
# through all pages of a PlomWiki via Action_Search().

$s = ReadStringsFile($plugin_strings_dir.'Search', $s);

$s['ActionLinks_Plugins'] .= $s['Search_ActionLinks'];

function Action_Search() {
# Case-insensitive search through all pages' texts and titles.
  global $s, $nl, $pages_dir;

  # Produce search results HTML if $_GET['query'] is provided.
  $query = $_GET['query'];
  if ($query) {
    if (get_magic_quotes_gpc())
      $query = stripslashes($query);
    $s['Search_query']       = $query;
    $s['Search_queryNoHTML'] = EscapeHTML($query);

    # Collect titles of pages matching $query into $matches, formatted.
    $matches   = array();
    $query_low = strtolower($query);
    foreach (GetAllPageTitles() as $title) {
      $content_low = strtolower(file_get_contents($pages_dir.$title));
      if (strstr($content_low, $query_low)
          or strstr(strtolower($title), $query_low)) {
        $s['i_title'] = $title;
        $matches[]    = ReplaceEscapedVars($s['Search():match']); } }

    # Format results display depending on matches found or not.
    $s['Search_Matches'] .= implode($nl, $matches); 
    if ($s['Search_Matches'])
      $s['Search():results'] .= $s['Search():ResultsYes'];
    else
      $s['Search():results'] .= $s['Search():ResultsNone']; }

  # Don't show *any* search results content if no query was provided.
  else
    $s['Search():results'] = '';

  $s['title']   = $s['Search():title'];
  $s['content'] = $s['Search():content'];
  OutputHTML(); }