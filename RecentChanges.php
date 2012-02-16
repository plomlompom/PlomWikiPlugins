<?php
# PlomWiki plugin: RecentChanges
# 
# Provides Action_RecentChanges() to output a "Recent Changes" list;
# also hooks RecentChanges_Add() into WritePage() to protocol all page
# changes in a RecentChanges.

$s = ReadStringsFile($plugin_strings_dir.'RecentChanges', $s);

# RecentChanges-specific global variables.
$RecentChanges_dir  = $plugin_dir.'RecentChanges/';
$RecentChanges_path = $RecentChanges_dir.'RecentChanges';

# WritePage() hook: Prepare and write RecentChanges_Add() call to todo.
$hook_WritePage .= '
$del = \'FALSE\';
if ($text == \'delete\') $del = \'TRUE\';
$tmp = NewTemp();
$txt_PluginsTodo .= "
RecentChanges_Add(\'$title\', $timestamp, $del, \'$tmp\');";';

function RecentChanges_Add($title, $time, $del, $tmp) {
  # Add info of page change to RecentChanges file.
  global $diff_dir, $nl, $RecentChanges_dir, $RecentChanges_path;

  # Work was finished in a previous run if $tmp is no more, so exit.
  if (!is_file($tmp)) return;

  # Check for (create if needed) RecentChanges/, get old RC file text.
  if (!is_dir($RecentChanges_dir))
    mkdir($RecentChanges_dir);
  if (is_file($RecentChanges_path))
    $RC_txt = file_get_contents($RecentChanges_path);

  # If page is deleted, prepend '!' to the title and ignore all else.
  if ($del) $title = '!'.$title;

  # Otherwise, get metadata from newest diff from page's diff list. 
  else {
    $diffs  = DiffList($diff_dir.$title);
    $id     = count($diffs) - 1;
    $author = $diffs[$id]['author'];
    $sum    = $diffs[$id]['summary'];

    # If newest diff has id=0, the page was newly created: prepend '+'.
    if ($id == 0) $title = '+'.$title; }

  # Add new data to new RC file text, write result atomically. Note that
  # this procedure provide a last list element with one empty line; see
  # Action_RecentChanges() comments on why this is important.
  $RC_txt = $time.$nl.$title.$nl.$id.$nl.$author.$nl.$sum.$nl.'%%'.$nl.
                                                                $RC_txt;
  file_put_contents($tmp, $RC_txt); 
  rename($tmp, $RecentChanges_path); }

function Action_RecentChanges() {
# Provide HTML output of RecentChanges file.
  global $s, $nl, $RecentChanges_path;

  # Format RecentChanges file content into HTML output.
  if (is_file($RecentChanges_path)) {
    $txt       = file_get_contents($RecentChanges_path);
    $lines     = explode($nl, $txt);

    # Count lines of each entry in RC file, starting anew at '%%'.
    foreach ($lines as $n => $line) {
      $i++;
      if  ('%%' == $line) $i = 0;

      # From 1st line, get date and time. If new date, finalize / output
      # previous day's entry list. Don't count the new date of the 1st
      # entry as new. The empty last line of RC file will be handled as
      # a date line too, triggering one last "date changed" event.
      if  (1    == $i) {
        $datetime                      = date('Y-m-d H:i:s',(int)$line);
        list($s['i_date'],$s['i_time'])= explode(' ', $datetime);
        if ($s['i_old_date'] and $s['i_old_date'] != $s['i_date']) {
          $s['RecentChanges_DayList'] .= ReplaceEscapedVars(
                                 $s['Action_RecentChanges():DayEntry']);
          $s['i_day'] = ''; }
        $s['i_old_date'] = $s['i_date']; }

      # Get title from 2nd line. If 1st char is + or -, save to $state.
      elseif (2 == $i) {
        $s['i_title'] = $line; 
        if ('!' == $s['i_title'][0] or '+' == $s['i_title'][0]) {
          $state        = $s['i_title'][0];
          $s['i_title'] = substr($s['i_title'], 1); } 
        $s['i_title_url'] = $s['title_root'].$s['i_title']; }
        
      # Harvest remaining data from lines 3, 4 and 5.
      elseif (3 == $i)
        $s['i_id'] = $line;
      elseif (4 == $i)
        $s['i_author'] = $line;
      elseif (5 == $i) {
        $s['i_summ'] = $line;
        
        # After reaching 5th line, build whole list entry for this diff.
        $s['i_diff'] = ReplaceEscapedVars(
                                     $s['Action_RecentChanges():Diff']);
        if     ('!' == $state) {
          $s['i_title'] = ReplaceEscapedVars(
                                 $s['Action_RecentChanges():DelTitle']);
          $s['i_diff']  = ''; }
        elseif ('+' == $state)
          $s['i_title'] = ReplaceEscapedVars(
                                 $s['Action_RecentChanges():AddTitle']);
        $state = '';
        $s['i_day'] .= ReplaceEscapedVars(
                            $s['Action_RecentChanges():DiffEntry']); } }

  # Either output formatted RC list, or message about its non-existence.
    $s['content'] = $s['Action_RecentChanges():list']; }
  else 
    $s['content'] = $s['Action_RecentChanges():NoRecentChanges'];
  $s['title'] = $s['Action_RecentChanges():title'];
  OutputHTML(); }
