<?php
# PlomWiki plugin: CommentsRepair. Repairs confused dates in comments
# files from a correct RecentComments file.
# 
# Copyright 2010-2012 Christian Heller / <http://www.plomlompom.de/>
# License: AGPLv3 or any later version. See file LICENSE for details.
#
# DEPENDENCIES: This assumes that the Comments.php plugin is activated.

$s = ReadStringsFile($plugin_strings_dir.'CommentsRepair', $s);
$s['ActionLinks_Plugins'] .= $s['CommentsRepair_ActionLinks'];

function Action_CommentsRepair() {
# Dialog offering comment date repair if RecentComments file is present.
  global $s, $Comments_Recent_path;
  if (is_file($Comments_Recent_path))
    $s['content'] = $s['Action_CommentsRepair():form'];
  else
    $s['content'] = $s['Action_CommentsRepair():No_RecentComments'];
  $s['title'] = $s['Action_CommentsRepair():title'];
  OutputHTML(); }

function PrepareWrite_CommentsRepair() {
# Prepare comment file rewrite with dates from RecentComments file.
  global $Comments_dir, $nl;

  # Build table of correct comment id : datetime mappings from RC file.
  $rc = Comments_GetRecentComments();
  foreach ($rc as $n => $entry)
    $correct[$entry['title']][$entry['id']] = $entry['datetime'];

  # Rebuild comment file texts according to $correct datetime table.
  foreach ($correct as $title => $cor_dates) {
    $txt = '';
    $comment_file = $Comments_dir.$title;
    $comment_list = Comments_GetComments($comment_file, 'all');
    foreach ($comment_list as $id => $x)
      $txt .= Comments_FileEntry($x['visibility'], $id, $cor_dates[$id],
                                 $x['author'], $x['url'], $x['text']);

    # Prepare writing of new comment file texts.
    $tmp   = NewTemp($txt);                                 
    $tasks .= 'if (is_file("'.$tmp.'")) '.
                'rename("'.$tmp.'", "'.$comment_file.'");'.$nl; }
  return $tasks; }
