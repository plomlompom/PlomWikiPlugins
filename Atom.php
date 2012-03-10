<?php
# PlomWiki plugin: Atom
# 
# Provides Atom feeds. (Currently only for the comments of the Comments
# plugin; via Action_AtomComments().)

$s = ReadStringsFile($plugin_strings_dir.'Atom', $s);

$Atom_dir                   = $plugin_dir.'Atom/';
$Atom_path_CommentsFeedID   = $Atom_dir.'AtomComments_ID';
$Atom_path_CommentsFeedName = $Atom_dir.'AtomComments_Name';

if (!is_dir($Atom_dir)) mkdir($Atom_dir);

# Atom feed for comments

function Action_AtomComments() {
# Output Atom feed of recent comments.
  global $Atom_dir, $Atom_path_CommentsFeedID, $Atom_path_CommentsFeedName, $nl,
         $now, $s, $Comments_dir, $Comments_Recent_path;

  if (is_file($Comments_Recent_path)) {
    $s['Atom_Domain']  = $_SERVER['SERVER_NAME'];
    $s['Atom_RootDir'] = dirname($_SERVER['REQUEST_URI']);
    $s['Atom_RootURL'] = $s['Atom_Domain'].$s['Atom_RootDir'];
                                       
    if (is_file($Atom_path_CommentsFeedID))
      $s['Atom_FeedCommentsID'] = file_get_contents($Atom_path_CommentsFeedID);
    else {
      $s['Atom_now']  = date('Y-m-d', (int) $now);
      $s['Atom_FeedCommentsID'] = ReplaceEscapedVars(
                                             $s['Atom_FeedCommentsID_pattern']);
      file_put_contents($Atom_path_CommentsFeedID, $s['Atom_FeedCommentsID']); }
    if (is_file($Atom_path_CommentsFeedName))
      $s['Atom_FeedCommentsName'] =
                                 file_get_contents($Atom_path_CommentsFeedName);
    else {
      file_put_contents($Atom_path_CommentsFeedName,
                                                 $s['Atom_FeedCommentsName']); }

    $days       = $_GET['days'];
    if (!$days)
      $time_limit = 0;
    else
      $time_limit = $now - ($days * 24 * 60 * 60);
    $txt        = file_get_contents($Comments_Recent_path);
    $lines      = explode($nl, $txt);
    foreach ($lines as $line) {
      $i++;
      if ('%%' == $line) {
        $comments = Comments_GetComments($Comments_dir.$s['i_title']);
        $text = Comments_FormatText($comments[$s['i_id']]['text']);
        $s['i_text'] = EscapeHTML($text);
        $s['Atom_Entries'] .= ReplaceEscapedVars($s['Atom_Entry']);
        $i = 0; }
      else if (1 == $i) {
        if ((int) $line < $time_limit)
          break;
        $s['i_datetime'] = date(DATE_ATOM, (int) $line);
        $s['i_date']     = date('Y-m-d', (int) $line);
        if (!$s['Atom_UpDate'])
          $s['Atom_UpDate'] = $s['i_datetime']; }
      else if (2 == $i)
        $s['i_author']   = EscapeHTML($line);
      else if (3 == $i)
        $s['i_title']    = $line;
      else if (4 == $i)
        $s['i_id']       = $line; }

    $s['design'] = $s['Action_AtomComments():output']; 
    header('Content-Type: application/atom+xml; charset=utf-8'); }

  else ErrorFail('Atom_NoFeed');

  OutputHTML(); }

function Action_AtomAdmin() {
  global $s;
  $s['content'] = $s['Action_AtomAdmin():form'];
  $s['title']   = $s['Action_AtomAdmin():title'];
  OutputHTML(); }

function PrepareWrite_AtomAdmin() {
  global $Atom_path_CommentsFeedName, $nl, $s;
  if (is_file($Atom_path_CommentsFeedName))
    $s['Atom_FeedCommentsName']=file_get_contents($Atom_path_CommentsFeedName);
  $tmp = NewTemp($_POST['NameCommentsFeed']);
  $tasks .= 'if (is_file("'.$tmp.'")) rename("'.$tmp.'", "'.
                                          $Atom_path_CommentsFeedName.'");'.$nl; 
  return $tasks; }
