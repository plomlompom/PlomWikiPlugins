<?php
# PlomWiki plugin: Atom
# 
# Provides Atom feeds for comments via Action_AtomComments() and for
# page edits via Action_AtomDiffs().

$s = ReadStringsFile($plugin_strings_dir.'Atom', $s);

$Atom_dir                   = $plugin_dir.'Atom/';
$Atom_path_CommentsFeedID   = $Atom_dir.'AtomComments_ID';
$Atom_path_CommentsFeedName = $Atom_dir.'AtomComments_Name';
$Atom_path_DiffsFeedID      = $Atom_dir.'AtomDiffs_ID';
$Atom_path_DiffsFeedName    = $Atom_dir.'AtomDiffs_Name';

if (!is_dir($Atom_dir)) mkdir($Atom_dir);

# Functions common to all feed functions.

function Atom_InitializeS(&$s, $string) {
# Set important variables for feed generation: URLs, ID, name, length.
  global $Atom_path_DiffsFeedID,   $Atom_path_CommentsFeedID,
         $Atom_path_DiffsFeedName, $Atom_path_CommentsFeedName, $now;

  $s['Atom_Domain']   = $_SERVER['SERVER_NAME'];
  $s['Atom_RootDir']  = dirname($_SERVER['REQUEST_URI']);
  $s['Atom_RootURL']  = $s['Atom_Domain'].$s['Atom_RootDir']; 

  $name_var           = 'Atom_path_'.$string.'FeedID';
  $path_FeedID        = $$name_var;
  $name_var           = 'Atom_path_'.$string.'FeedName';
  $path_FeedName      = $$name_var;
  $key_FeedID         = 'Atom_Feed'.$string.'ID';
  $key_FeedName       = 'Atom_Feed'.$string.'Name';
  $key_FeedID_pattern = 'Atom_Feed'.$string.'ID_pattern';
  if (is_file($path_FeedID))
    $s[$key_FeedID] = file_get_contents($path_FeedID);
  else {
    $s['Atom_now']  = date('Y-m-d', (int) $now);
    $s[$key_FeedID]=ReplaceEscapedVars($s[$key_FeedID_pattern]);
    file_put_contents($path_FeedID, $s[$key_FeedID]); }
  if (is_file($path_FeedName))
    $s[$key_FeedName] = file_get_contents($path_FeedName);
  else
    file_put_contents($path_FeedName, $s[$key_FeedName]);

  $days = $_GET['days'];
  if (!$days) $s['Atom_TimeLimit'] = $now - (10    * 24 * 60 * 60);
  else        $s['Atom_TimeLimit'] = $now - ($days * 24 * 60 * 60); }

function Atom_SetDate(&$s, $line) {
  $s['i_datetime'] = date(DATE_ATOM, (int) $line);
  $s['i_date']     = date('Y-m-d', (int) $line);
  if (!$s['Atom_UpDate'])
    $s['Atom_UpDate'] = $s['i_datetime']; }

# Atom feed for page diffs.

function Action_AtomDiffs() {
# Output Atom feed of recent diffs.
  global $nl, $s, $RecentChanges_dir, $RecentChanges_path;

  # Determine format for diff display.
  $format = $_GET['format'];
  $s['Atom_DiffEntryType'] = 'text';
  if ('color' == $format or 'linebreaks' == $format)
    $s['Atom_DiffEntryType'] = 'html';

  if (is_file($RecentChanges_path)) {
    Atom_InitializeS($s, 'Diffs');
    $txt        = file_get_contents($RecentChanges_path);
    $lines      = explode($nl, $txt);
    foreach ($lines as $line) {
      $i++;
      if ('%%' == $line) {
        if ($s['Atom_DiffDel']==$state) $s['i_content'] = $s['i_summ'];
        else                            Atom_DiffContent($s, $format);
        $s['Atom_Entries'] .= ReplaceEscapedVars($s['Atom_DiffEntry']);
        $i                  = 0; 
        $state              = ''; }
      else if (1 == $i) {
        if ((int) $line < $s['Atom_TimeLimit']) break;
        Atom_SetDate($s, $line); }
      else if (2 == $i) {
        if      ('+' == $line[0]) $state        = $s['Atom_DiffAdd'];
        else if ('!' == $line[0]) $state        = $s['Atom_DiffDel'];
        if      (''  == $state  ) $s['i_title'] = $line;
        else                      $s['i_title'] = substr($line, 1);
        $s['i_title_formatted'] = $state.$s['i_title']; }
      else if (3 == $i)
        if ($s['Atom_DiffDel']==$state)
          $s['i_id']     = $s['Atom_DiffDelID'];
        else
          $s['i_id']     = $line; 
      else if (4 == $i)
        if ($s['Atom_DiffDel']==$state)
          $s['i_author'] = $s['Atom_DiffDelAuthor'];
        else
          $s['i_author'] = EscapeHTML($line); 
      else if (5 == $i)
        if ($s['Atom_DiffDel']==$state)
          $s['i_summ']   = $s['Atom_DiffDelSumm'];
        else
          $s['i_summ']   = EscapeHTML($line); }

    $s['design'] = $s['Action_AtomDiffs():output']; 
    header('Content-Type: application/atom+xml; charset=utf-8'); }
  else ErrorFail('Atom_NoFeed');
  OutputHTML(); }

function Atom_DiffContent(&$s, $format = '') {
# Build $s['i_content'] based on $format and existence of diff entry.
  global $diff_dir, $nl;
  $diff_path = $diff_dir.$s['i_title'];
  if (!is_file($diff_path)) {
    $s['i_content'] = $s['Atom_NoDiff'];
    return; }
  $diff_data = DiffList($diff_path);
  $diff_text = $diff_data[$s['i_id']]['text'];
  if (!$diff_text) {
    $s['i_content'] = $s['Atom_NoDiff'];
    return; }
  $s['i_content'] = '';

  # Colorful display of diffs as used by Action_page_history().
  if ('color' == $format)
    foreach (explode($nl, $diff_text) as $line_n => $line) {
      if     ($line[0] == '>')
        $theme = 'Atom_diff_ins';
      elseif ($line[0] == '<')
        $theme = 'Atom_diff_del';
      else
        $theme = 'Atom_diff_meta';
      if ($line[0] == '<' or $line[0] == '>') 
        $line = EscapeHTML(substr($line, 1));
      $s['line'] = $line;
      $s['i_content'] .= EscapeHTML(ReplaceEscapedVars($s[$theme])); }

  # Format $diff_text newlines to HTML <br />s.
  else if ('linebreaks' == $format) {
    $diff_text      = str_replace($nl, '<br />'.$nl, $diff_text);
    $s['i_content'] = EscapeHTML($diff_text); }

  # Fallback: raw output of $diff_text.
  else 
    $s['i_content'] = EscapeHTML($diff_text); }

# Atom feed for comments.

function Action_AtomComments() {
# Output Atom feed of recent comments.
  global $nl, $s, $Comments_dir, $Comments_Recent_path;

  if (is_file($Comments_Recent_path)) {
    Atom_InitializeS($s, 'Comments');
    $txt        = file_get_contents($Comments_Recent_path);
    $lines      = explode($nl, $txt);
    foreach ($lines as $line) {
      $i++;
      if ('%%' == $line) {
        $comments = Comments_GetComments($Comments_dir.$s['i_title']);
        $text = Comments_FormatText($comments[$s['i_id']]['text']);
        $s['i_text'] = EscapeHTML($text);
        $s['Atom_Entries'].=ReplaceEscapedVars($s['Atom_CommentEntry']);
        $i = 0; }
      else if (1 == $i) {
        if ((int) $line < $s['Atom_TimeLimit']) break;
        Atom_SetDate($s, $line); }
      else if (2 == $i) $s['i_author'] = EscapeHTML($line);
      else if (3 == $i) $s['i_title']  = $line;
      else if (4 == $i) $s['i_id']     = $line; }

    $s['design'] = $s['Action_AtomComments():output']; 
    header('Content-Type: application/atom+xml; charset=utf-8'); }
  else ErrorFail('Atom_NoFeed');
  OutputHTML(); }

# Admin.

function Action_AtomAdmin() {
  global $Atom_path_DiffsFeedName, $Atom_path_CommentsFeedName, $s;
  if (is_file($Atom_path_DiffsFeedName))
    $s['Atom_FeedDiffsName']    = file_get_contents(
                                              $Atom_path_DiffsFeedName);
  if (is_file($Atom_path_CommentsFeedName))
    $s['Atom_FeedCommentsName'] = file_get_contents(
                                           $Atom_path_CommentsFeedName);
  $s['content'] = $s['Action_AtomAdmin():form'];
  $s['title']   = $s['Action_AtomAdmin():title'];
  OutputHTML(); }

function PrepareWrite_AtomAdmin() {
  global $Atom_path_DiffsFeedName, $Atom_path_CommentsFeedName, $nl, $s;
  $tmp = NewTemp($_POST['NameDiffsFeed']);
  $tasks .= 'if (is_file("'.$tmp.'")) rename("'.$tmp.'", "'.
                                     $Atom_path_DiffsFeedName.'");'.$nl;
  $tmp = NewTemp($_POST['NameCommentsFeed']);
  $tasks .= 'if (is_file("'.$tmp.'")) rename("'.$tmp.'", "'.
                                  $Atom_path_CommentsFeedName.'");'.$nl;
  return $tasks; }
