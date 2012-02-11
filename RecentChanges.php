<?php
# PlomWiki plugin: RecentChanges
# 
# Provides Action_RecentChanges().

# Language-specific variables.
$l['RecentChanges']   = 'Recent Changes';
$l['NoRecentChanges'] = 'No RecentChanges file found.';

# RecentChanges-specific global variables.
$RecentChanges_dir  = $plugin_dir.'RecentChanges/';
$RecentChanges_path = $RecentChanges_dir.'RecentChanges';

# Hook into WritePage(): Prepare and write RecentChanges_Add() call to todo.
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

  # Check for (create if needed) RecentChanges tree, get old RC file text.
  if (!is_dir($RecentChanges_dir))
    mkdir($RecentChanges_dir);
  if (is_file($RecentChanges_path))
    $RC_txt = file_get_contents($RecentChanges_path);

  # If page is deleted, merely prepend '!' to the title and ignore all else.
  if ($del) $title = '!'.$title;

  # Otherwise, get metadata from newest diff (highest id) from page's diff list. 
  else {
	$diffs  = DiffList($diff_dir.$title);
	$id     = count($diffs) - 1;
    $author = $diffs[$id]['author'];
    $sum    = $diffs[$id]['summary'];

    # If the newest diff has id=0, the page was newly created, so prepend '+'.
    if ($id == 0) $title = '+'.$title; }

  # Add new data to new RC file text, write result atomically.
  $RC_txt = $time.$nl.$title.$nl.$id.$nl.$author.$nl.$sum.$nl.'%%'.$nl.$RC_txt;
  file_put_contents($tmp, $RC_txt); 
  rename($tmp, $RecentChanges_path); }

function Action_RecentChanges()
# Provide HTML output of RecentChanges file.
{ global $esc, $l, $nl, $nl2, $RecentChanges_path, $title_root;

  # Format RecentChanges file content into HTML output.
  $output = '';
  if (is_file($RecentChanges_path)) 
  { $txt = file_get_contents($RecentChanges_path);
    $lines    = explode($nl, $txt);
    $i        = 0;
    $date_old = $state = $state_on = $state_off = '';
    foreach ($lines as $line)
    { $i++;
      if ('%%' == $line)
        $i = 0;
      if     (1 == $i) 
      { $datetime   = date('Y-m-d H:i:s', (int) $line);
        list($date, $time) = explode(' ', $datetime); }
      elseif (2 == $i)
      { $title  = $line; 
        if ('!' == $title[0] or '+' == $title[0]) 
        { $state = $title[0];
          $title = substr($title, 1); } }
      elseif (3 == $i)
        $id     = $line;
      elseif (4 == $i)
        $author = $line;
      elseif (5 == $i) 
      { $diff_link = ' <a href="'.$title_root.$title.'&amp;action=page_history#'
                                                                 .$id.'">#</a>';
        if     ('!' == $state)
        { $state_on  = '<del>';
          $state_off = '</del>';
          $diff_link=''; }
        elseif ('+' == $state)
        { $state_on='<strong>';
          $state_off='</strong>'; }
        $string = '               <li>'.$time.' <a href="'.$title_root.$title.
                   '">'.$state_on.$title.$state_off.'</a>'.$diff_link.': '.$line
                                                         .' ('.$author.')</li>';
        $state = $state_on = $state_off = '';
        if ($date != $date_old)
        { $string = substr($string, 15);
          $string = '          </ul>'.$nl.'     </li>'.$nl.'     <li>'.$date.$nl
                                                     .'          <ul> '.$string;
          $date_old = $date; }
        $list[] = $string; } }
    $list[0] = substr($list[0], 15);
    $output = '<ul>'.implode($nl, $list).$nl.'          </ul>'.$nl.'     </li>'.
                                                                  $nl.'</ul>'; }
  else 
    $output = '<p>'.$esc.'NoRecentChanges'.$esc.'</p>';
  
  # Final HTML.
  $l['title'] = $esc.'RecentChanges'.$esc; $l['content'] = $output;
  OutputHTML(); }
