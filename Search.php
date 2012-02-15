<?php
# PlomWiki plugin: Search
# 
# Provides a very simple case-insensitive full-text string search
# through all pages of a PlomWiki via Action_Search().

$l = ReadReg($plugin_regs_dir.'Search', $l);

function Action_Search() {
# Case-insensitive search through all pages' texts and titles.
  global $l, $nl, $pages_dir;

  # Produce search results HTML if $_GET['query'] is provided.
  $query = $_GET['query'];
  if ($query) {
    if (get_magic_quotes_gpc())
      $query = stripslashes($query);
    $l['Search_query']       = $query;
    $l['Search_queryNoHTML'] = EscapeHTML($query);

    # Collect titles of pages matching $query into $matches, formatted.
    $matches   = array();
    $query_low = strtolower($query);
    foreach (GetAllPageTitles() as $title) {
      $content_low = strtolower(file_get_contents($pages_dir.$title));
      if (strstr($content_low, $query_low)
          or strstr(strtolower($title), $query_low)) {
        $l['i_title'] = $title;
        $matches[]    = ReplaceEscapedVars($l['Search():match']); } }

    # Format results display depending on matches found or not.
    $l['Search_Matches'] .= implode($nl, $matches); 
    if ($l['Search_Matches'])
      $l['Search():results'] .= $l['Search():ResultsYes'];
    else
      $l['Search():results'] .= $l['Search():ResultsNone']; }

  # Don't show *any* search results content if no query was provided.
  else
    $l['Search():results'] = '';

  $l['title']   = $l['Search():title'];
  $l['content'] = $l['Search():content'];
  OutputHTML(); }