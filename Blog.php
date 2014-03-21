<?php
# PlomWiki plugin: Blog. Morph wiki pages into a simple blog's articles.
#
# Copyright 2014 Christian Heller / <http://www.plomlompom.de/>
# License: AGPLv3 or any later version. See file LICENSE for details.

$s = ReadStringsFile($plugin_strings_dir.'Blog', $s);
$Blog_dir                 = $plugin_dir.'Blog/';
$Blog_path_ArticleDB      = $Blog_dir.'articles';
$Blog_ArticleDB_separator = ':';
$s['ActionLinks_Plugins']      .= $s['Blog_ActionLinks'];
$s['ActionLinks_page_Plugins'] .= $s['Blog_page_ActionLinks'];

#On each page update, (re-)set or delete page's entry in Blog DB.
$hook_WritePage .= '
$Blog_tmp = NewTemp();
$Blog_del = \'FALSE\';
if ($text == \'delete\')
  $Blog_del = \'TRUE\';
if ((\'FALSE\' == $Blog_del) and !(Blog_PageIsArticle($title)))
  $Blog_del = \'TRUE\';
$txt_PluginsTodo .= "
Blog_UpdatePageAsArticle(\'$title\', $Blog_del, \'$Blog_tmp\');";
';

# Prepend date display to blogified articles in "page_view" action.
$hook_OutputHTML .= '
global $action, $pages_dir, $diff_dir, $title;
$diff_path = $diff_dir.$title;
if ($action == \'Action_page_view\'
   and Blog_PageIsArticle($title) and is_file($diff_path)) {
  $s[\'Blog_Date\'] = Blog_getDateStringFromDiff($diff_path);
  $s[\'BelowTitle\'] .= $s[\'Blog_BelowTitle\']; }
';

function Blog_getDB() {
# Return Blog DB in easily readable form.
  global $Blog_path_ArticleDB, $Blog_ArticleDB_separator, $nl;
  $entries = array();
  if (is_file($Blog_path_ArticleDB)) {
    $lines = explode($nl, file_get_contents($Blog_path_ArticleDB));
    foreach ($lines as $line) {
      $title_and_datetime = explode($Blog_ArticleDB_separator, $line);
      $entries[$title_and_datetime[0]] = $title_and_datetime[1]; }
    unset($entries['']); } # handle last $nl-line
  return $entries; }

function Blog_PageIsArticle($title) {
# Return TRUE if page of $title is a blog article, else FALSE.
  $entries = Blog_getDB();
  return $entries[$title]; }

function Blog_UpdatePageAsArticle($title, $del, $tmp) {
# Add or ($del) remove $title in (temporarily stored at $tmp) BlogDB.
# Sort articles chronologically (newest by creation date first).
  global $Blog_dir, $Blog_path_ArticleDB, $Blog_ArticleDB_separator,
         $nl, $diff_dir;
  if (!is_file($tmp))
    return;
  if (!is_dir($Blog_dir))
    mkdir($Blog_dir);
  $entries = Blog_getDB();
  if ($del)
    unset($entries[$title]);
  else {
    $diff_path = $diff_dir.$title;
    $diff_list = DiffList($diff_path);
    $entries[$title] = $diff_list[0]['time']; }
  arsort($entries);
  foreach ($entries as $title => $datetime)
    $db_text .= $title.$Blog_ArticleDB_separator.$datetime.$nl;
  file_put_contents($tmp, $db_text);
  rename($tmp, $Blog_path_ArticleDB); }

function Action_page_Blogify() {
# Display form for blogifying / toggling state of page as blog article.
  global $s, $title;
  $s['title']   = $s['Action_page_Blogify():title'];
  $s['content'] = $s['Action_page_Blogify():form'];
  if (Blog_PageIsArticle($title))
    $checked = 'checked';
  $s['Action_page_Blogify():checked'] = $checked;
  OutputHTML(); }

function PrepareWrite_Blog_Article(&$redir) {
# Prepare todo for setting the state of the wiki page as a blog article.
  global $nl, $page_path, $s, $title;
  $redir = $s['title_url'];
  $del = 'FALSE';
  $checked = $_POST['blog'];
  if ('on' != $checked) $del = 'TRUE';
  $s['PrepareWrite_Blog_Article():pagetitle'] = $title;
  if (($del == 'FALSE') and !(is_file($page_path)))
    ErrorFail('PrepareWrite_Blog_Article():NoPage');
  $tmp = NewTemp();
  return 'Blog_UpdatePageAsArticle("'.$title.'", '.$del.', "'.$tmp.'");'
                                                                 .$nl; }

function Blog_getDateStringFromDiff($diff_path) {
# Return creation date for page of $diff_path in Y-m-d format.
  $diff_list = DiffList($diff_path);
  $date = $diff_list[count($diff_list) - 1]['time'];
  return date('Y-m-d', (int) $date); }

function Blog_SetOverviewArticleData($title) {
# Set fields defining display of page $title in blog overview display.
  global $Comments_dir, $DisplayTitle_dir, $diff_dir, $pages_dir, $s;
  $s['i_title'] = $title;
  $s['i_date'] = Blog_getDateStringFromDiff($diff_dir.$title);
  $s['Blog_SetOverviewArticleData():Meta'] = ReplaceEscapedVars(
                  $s['Blog_SetOverviewArticleData():MetaSansComments']);
  if (is_dir($Comments_dir)) {
    $s['i_comments_n'] = 0;
    $path_comments = $Comments_dir.$title;
    if (is_file($path_comments))
      $s['i_comments_n'] =count(Comments_GetComments($path_comments));
    $s['Blog_SetOverviewArticleData():Meta'] = ReplaceEscapedVars(
                $s['Blog_SetOverviewArticleData():MetaWithComments']); }
  $s['i_display_title'] = $title;
  if (is_dir($DisplayTitle_dir))
    $s['i_display_title'] = DisplayTitle_get($title);
  $s['i_content'] = Markup(file_get_contents($pages_dir.$title)); }

function Action_Blog() {
  # Display selection of blog articles.
  global $nl, $s;

  # ?start= and ?how_many determine article selection to display.
  $s['Action_Blog():HowMany'] = $_GET['how_many'];
  if ($s['Action_Blog():HowMany'] < 1)
    $s['Action_Blog():HowMany'] = 5;
  $start = $_GET['start'];
  if (0 == $start)
    $start = 1;

  # Generate display of articles selected.
  foreach (array_keys(Blog_getDB()) as $title) {
    $i++;
    if ($i < $start or $i > $start + $s['Action_Blog():HowMany'] - 1)
      continue;
    Blog_SetOverviewArticleData($title);
    $article = ReplaceEscapedVars($s['Action_Blog():Article']);
    $articles .= $article.$s['Action_Blog():Separator']; }
  $len_sep = strlen($s['Action_Blog():Separator']);
  if ($len_sep)
    $articles = substr($articles, 0, -$len_sep);

  # Attach appropriate navigation links to finalized articles display.
  if ($start > 1) {
    $s['Action_Blog():new_start'] = $start -$s['Action_Blog():HowMany'];
    if ($s['Action_Blog():new_start'] < 1)
      $s['Action_Blog():new_start'] = 1;
    $s['Action_Blog():NavNew'] =
               ReplaceEscapedVars($s['Action_Blog():NavNew_pattern']); }
  if ($start + $s['Action_Blog():HowMany'] - 1 < $i) {
    $s['Action_Blog():new_start'] = $start+$s['Action_Blog():HowMany'];
    $s['Action_Blog():NavOld'] = $s['Action_Blog():NavOld_pattern']; }
  if ($s['Action_Blog():NavOld'] or $s['Action_Blog():NavNew'])
    $s['Action_Blog():Footer'] = $s['Action_Blog():NavFooter'];
  $s['content'] = $articles.$s['Action_Blog():Footer'];
  $s['title'] = $s['Action_Blog():Title'];
  OutputHTML(); }

function Action_Blog_Atom() {
# Display Atom feed of last blog articles.
  global $Blog_dir,$Blog_path_AtomStart, $diff_dir, $now, $pages_dir,$s;
  $Blog_path_AtomStart = $Blog_dir.'AtomStart';
  $s['Blog_Domain'] = $_SERVER['SERVER_NAME'];
  $s['Blog_RootDir'] = dirname($_SERVER['REQUEST_URI']);
  $s['Blog_RootURL'] = $s['Blog_Domain'].$s['Blog_RootDir'];
  if (!is_dir($Blog_dir))
    mkdir($Blog_dir);

  # Set/get feed start date.
  if (is_file($Blog_path_AtomStart))
    $s['Blog_AtomStart'] = file_get_contents($Blog_path_AtomStart);
  else {
    $s['Blog_AtomStart']  = date('Y-m-d', (int) $now);
    file_put_contents($Blog_path_AtomStart, $s['Blog_AtomStart']); }

  # Determine length of history to display from ?how_many=.
  $max_i = $_GET['how_many'];
  if ($max_i < 1)
    $max_i = 5;

  # Build feed display for article selection just determined.
  foreach (array_keys(Blog_getDB()) as $title) {
    $i++;
    $diff_path = $diff_dir.$title;
    $diff_list = DiffList($diff_path);
    $i_date = $diff_list[count($diff_list) - 1]['time'];
    if ($i_date > $last_update)
      $last_update = $i_date;
    if ($i > $max_i)
      continue; # Don't break; look beyond for final $last_update.
    $s['i_datetime'] = date(DATE_ATOM, (int) $i_date);
    $s['i_date'] = date('Y-m-d', (int) $i_date);
    $s['i_title'] = $title;
    $s['i_author'] =
                EscapeHTML($diff_list[count($diff_list) - 1]['author']);
    $page_path = $pages_dir.$title;
    $s['i_content'] = EscapeHTML(Markup(file_get_contents($page_path)));
    $s['Action_Blog_Atom():Articles'] .=
                 ReplaceEscapedVars($s['Action_Blog_Atom():Article']); }
  $s['Action_Blog_Atom():LastUpdate'] = date(DATE_ATOM,
                                                    (int) $last_update);
  $s['design'] = $s['Action_Blog_Atom():output'];
  header('Content-Type: application/atom+xml; charset=utf-8');
  OutputHTML(); }
