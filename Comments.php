<?php
# PlomWiki plugin: Comments
# 
# Provides moderated comments.

$s = ReadStringsFile($plugin_strings_dir.'Comments', $s);

$s['ActionLinks_Plugins'] .= $s['Comments_ActionLinks'];

$Comments_dir              = $plugin_dir.'Comments/';
$Comments_captcha_path     = $Comments_dir.'_captcha';
$Comments_Recent_path      = $Comments_dir.'_RecentComments';

$s['Comments_key']         = 'Comments_captcha';
$legal_pw_key             .= '|'.$s['Comments_key'];
$permissions['Comments'][] = $s['Comments_key'];

$s['code'] .= '
$hook_before_action .= $s["Comments_HookBeforeAction"]; ';

###########################
# Common comments display #
###########################

function Comments() {
# Return display of page comments and commenting form.
  global $Comments_captcha_path, $Comments_dir, $pages_dir, $s;

  # Silently fail if $Comments_dir or page do not exist.
  if (!is_dir($Comments_dir) or !is_file($pages_dir.$s['page_title']))
    return;

  # Build comments display -- or show message about lack of comments.
  $cur_page_file = $Comments_dir.$s['page_title'];
  if (is_file($cur_page_file)) {
    $comment_list = Comments_GetComments($cur_page_file, 0, $ignored);
    $s['Comments():Comments']=Comments_BuildCommentsList($comment_list);
    if (0 < $ignored) {
      $s['Comments_ignored']     = $ignored;
      $s['Comments():Comments'] .= $s['Comments_Hidden']; } }
  else
    $s['Comments():Comments'] = $s['Comments():None'];

  # Output commenting $form --  if $Comments_captcha_path file exists.
  $s['Comments():Input'] = $s['Comments():FormHead'];
  if (is_file($Comments_captcha_path)) {
    $s['Comments_captcha'] = file_get_contents($Comments_captcha_path);
    $s['Comments():Input'] .= $s['Comments():form']; }
  else
    $s['Comments():Input'] .= $s['Comments():formNo'];

  # Finally, put everything together.
  return $s['Comments():Output']; }

function Comments_BuildCommentsList($comment_list) {
# From comment_list build $s['Comments():Comment']-formatted output.
  global $s;
  foreach ($comment_list as $id => $x) {
    $s['i_id']         = $id;
    $s['i_visibility'] = $x['visibility'];
    $s['i_mod']        = ReplaceEscapedVars($s['Comments_Mod']);
    $s['i_datetime']   = date('Y-m-d H:i:s', (int) $x['datetime']);
    $s['i_author']     = $x['author'];
    $s['i_url']        = $x['url'];
    if ($s['i_url'])
      $s['i_author']   = ReplaceEscapedVars($s['Comments_AuthorURL']);
    $s['i_text']       = Comments_FormatText($x['text']);
    $list             .= ReplaceEscapedVars($s['Comments():Comment']); }
  return $list; }

function Comments_FormatText($text) {
# Comment formatting: EscapeHTML, paragraphing / line breaks.
  global $nl;
  $text      = EscapeHTML($text);
  $lines     = explode($nl, $text);
  $last_line = '';
  foreach ($lines as $n => $line) {
    if     (''  == $last_line and '' !== $line)
      $lines[$n] = '<p>'.$line;
    elseif ('' !== $last_line and ''  == $line)
      $lines[$n] = '</p>'.$nl;
    elseif ('' !== $last_line and '' !== $line)
      $lines[$n] = '<br />'.$nl.$line;
    $last_line = $line; }
  $text = implode($lines);
  if ('</p>' !== substr($text, -4) and '</p>'.$nl !== substr($text, -5))
    $text = $text.'</p>';
  return $text; }

function Comments_GetComments($comment_file, $show=0, &$ignored=NULL ) {
# Read $comment_file into more structured, readable array $comments.
  global $nl;
  $comments = array();

  # Read comment data line by line, read each entry's start as metadata.
  $file_txt  = file_get_contents($comment_file);
  $ignored = 0;
  foreach (explode($nl.$nl, $file_txt) as $entry_txt) {
    if (!$entry_txt)
      continue;
    $time=''; $author=''; $url=''; $lines_comment=array(); $ignore=0;
    foreach (explode($nl, $entry_txt) as $line_n => $line) {
      if ($line_n == 0)
        if ('all' === $show or $show == $line)
          $visibility = $line;
        else {
          $ignore = 1;
          $ignored++;
          break; }
      elseif ($line_n==1)           $id              = $line;
      elseif ($line_n==2)           $datetime        = $line;
      elseif ($line_n==3)           $author          = $line;
      elseif ($line_n==4 and $line) $url             = substr($line,1);
      else                          $lines_comment[] = substr($line,1);}
    if (1 == $ignore)                   # Ignore entry if $ignore was
      continue;                         # set by visibility value check.
    $comments[$id]['visibility'] = $visibility;
    $comments[$id]['author']     = $author;
    $comments[$id]['datetime']   = $datetime;
    $comments[$id]['url']        = $url;
    $comments[$id]['text']       = implode($nl, $lines_comment); }

  return $comments; }

#######################
# Comments moderation #
#######################

function Action_page_Comments_mod() {
# Show comments to moderate for a page.
  global $s;
  $s['Comments_Mod'] = $s['Comments_ModYes'];
  $s['title']        = $s['Action_page_Comments_mod():title'];
  Comments_DisplayPage('all'); }

function Action_page_Comments_hidden() {
# Show the hidden comments for a page.
  global $s;
  $s['title']         = $s['Action_page_Comments_hidden:title'];
  $s['Comments_None'] = $s['Comments_NoneHidden'];
  Comments_DisplayPage(1); }

function Comments_DisplayPage($what = 0) {
# Use a list of comments to current page for current HTML output.
  global $Comments_dir, $pages_dir, $s;
  $cur_page_file = $Comments_dir.$s['page_title'];
  if (!is_dir($Comments_dir) or !is_file($pages_dir.$s['page_title'])
      or !is_file($cur_page_file))
    $s['content'] = $s['Comments():None'];
  if (is_file($cur_page_file)) {
    $comment_list = Comments_GetComments($cur_page_file, $what);
    $s['content'] = Comments_BuildCommentsList($comment_list); }
  OutputHTML(); }

function Action_page_Comments_ModToggle() {
# Display form asking whether to toggle visibility to page comment id=.
  global $s;
  $s['Comments_ID'] = $_GET['id'];
  $s['content']     = $s['Action_page_Comments_ModToggle():form'];
  $s['title']       = $s['Action_page_Comments_ModToggle():title'];
  OutputHTML(); }

function PrepareWrite_Comments_ToggleVisibility(&$redir) {
# Prepare visibility toggling of comment #id to page "title".
  global $Comments_dir, $Comments_Recent_path, $nl, $pages_dir, $s;
  $id = $_POST['id']; $title = $_POST['title'];

  $comment_file   = $Comments_dir.$title;
  $comment_list   = Comments_GetComments($comment_file, 'all');
  $new_visibility = 1;
  if (1 == $comment_list[$id]['visibility'])
    $new_visibility = 0;
  $comment_list[$id]['visibility'] = $new_visibility;
  foreach ($comment_list as $_id => $x)
    $txt_Comments .= Comments_FileEntry($x['visibility'], $_id, 
                      $x['datetime'],$x['author'],$x['url'],$x['text']);
  $tmp_Comments = NewTemp($txt_Comments);

  $recent_comments = Comments_GetRecentComments();
  foreach ($recent_comments as $n => $entry)
    if ($title == $entry['title'] and $id == $entry['id']) {
      $recent_comments[$n]['visibility'] = $new_visibility;
      break; }
  foreach ($recent_comments as $n => $y) if ($y['id'] !== '')
    $txt_Recent .= $y['visibility'].$nl.$y['datetime'].$nl.$y['author'].
                              $nl.$y['title'].$nl.$y['id'].$nl.'%%'.$nl; 
  $txt_Recent .= $nl;
  $tmp_Recent = NewTemp($txt_Recent);

  # Build $redir and task text.  
  $suffix = '&amp;action=page_Comments_mod#comment_'.$id;
  $redir  = $s['title_root'].$title.$suffix;
  return 'if (is_file("'.$tmp_Comments.'")) '.
          'rename("'.$tmp_Comments.'","'.$comment_file.'");'.$nl.
         'if (is_file("'.$tmp_Recent.'")) '.
          'rename("'.$tmp_Recent.'","'.$Comments_Recent_path.'");'.$nl;}

####################
# Comments writing #
####################

function PrepareWrite_Comments(&$redir) {
# Check for failure conditions, then prepare writing of comment to file.
  global $Comments_dir, $esc, $nl, $s;
  $author = $_POST['author'];
  $url    = $_POST['URL'];
  $text   = $_POST['text'];

  # Repair problematical characters in submitted texts.
  foreach (array('author', 'url', 'text') as $variable_name)
    $$variable_name = Sanitize($$variable_name);
  $author = str_replace("\xE2\x80\xAE", '', $author); # ForceRightToLeft

  # Check for failure conditions: empty variables, too large/bad values.
  if (!$author)
    ErrorFail('Comments_NoAuthor');
  if (!$text)
    ErrorFail('Comments_NoText');
  if (strlen($author) > $s['Comments_AuthorMax'])
    ErrorFail('Comments_AuthorMaxMsg');
  if (strlen($url) > $s['Comments_URLMax'])
    ErrorFail('Comments_URLMaxMsg');
  if (strlen($text) > $s['Comments_TextMax'])
    ErrorFail('Comments_TextMaxMsg');
  $legal_url = '[A-Za-z][A-Za-z0-9\+\.\-]*:([A-Za-z0-9\.\-_~:/\?#\[\]@!'
              .'\$&\'\(\)\*\+,;=]|%[A-Fa-f0-9]{2})+'; # Thx to erlehmann
  if ($url and !preg_match('{^'.$legal_url.'$}', $url))
    ErrorFail('Comments_InvalidURL');

  # Look into $cur_page_file for $old text and to generate $new_id.
  $cur_page_file = $Comments_dir.$s['page_title'];
  $new_id        = 0;
  if (is_file($cur_page_file)) {
    $old           = file_get_contents($cur_page_file);
    $prev_comments = Comments_GetComments($cur_page_file);
    $new_id        = count($prev_comments); }
  $redir = $s['title_url'].'#comment_'.$new_id;

  # Check for error condition: same text as last comment.
  if (is_file($cur_page_file))
    if ($text == $prev_comments[$new_id - 1]['text'])
      ErrorFail('Comments_Double');
  
  # Put all together into $add, add $old to get new comments file text.
  $time = time();
  $add = Comments_FileEntry('0', $new_id, time(), $author, $url, $text);

  # Return tasks for writing/update of Comments/RecentComments files.
  $tmp_Comms       = NewTemp($old.$add);
  $tmp_AddToRecent = NewTemp();
  $tmp_author      = NewTemp($author);
  return 'if (is_file("'.$tmp_Comms.'")) rename("'.$tmp_Comms.'", "'
                                              .$cur_page_file.'");'.$nl.
           'Comments_AddToRecent("'.$s['page_title'].'", '.$new_id.', '.
             $time.', "'.$tmp_AddToRecent.'", "'.$tmp_author.'", 0);'; }

function Comments_FileEntry($vis, $id, $time, $who, $url, $txt) {
# Format comment data into multi-line entry to comment file.
  global $nl;
  $lines = explode($nl, $txt);         # Prefix : to lines that could be
  $txt = ':'.implode($nl.':', $lines); # empty; empty line is separator.
  $time = time();
  return $vis.$nl.$id.$nl.$time.$nl.$who.$nl.':'.$url.$nl.$txt.$nl.$nl;}

###################
# Recent Comments #
###################

function Comments_GetRecentComments() {
# Read $Comments_Recent_path into structured array $recent_comments.
  global $Comments_Recent_path, $nl;
  $lines = explode($nl, file_get_contents($Comments_Recent_path));
  $n = 0;
  foreach ($lines as $line) {
    $i++;
    if ('%%'==$line) {
      $recent_comments[$n]['visibility'] = $vis;
      $recent_comments[$n]['datetime']   = $datetime;
      $recent_comments[$n]['author']     = $author;
      $recent_comments[$n]['title']      = $title; 
      $recent_comments[$n]['id']         = $id; 
      $i = 0;
      $n++; }
    elseif (1 == $i) $vis      = $line;
    elseif (2 == $i) $datetime = $line;
    elseif (3 == $i) $author   = $line;
    elseif (4 == $i) $title    = $line;
    elseif (5 == $i) $id       = $line; }
  return $recent_comments; }

function Action_Comments() {
# Provide HTML output of RecentComments file.
  global $nl, $s, $Comments_Recent_path;

  # Format RecentComments file content into HTML output.
  if (is_file($Comments_Recent_path)) {
    $recent_comments = Comments_GetRecentComments();

    # Output a finished day only after entries for a new day were found;
    # therefore, add a virtual last day to trigger last day list event.
    $recent_comments[] = array();
    foreach ($recent_comments as $n => $entry) {
      $date = date('Y-m-d', (int) $entry['datetime']);
      if ($s['i_old_date'] && $s['i_old_date'] != $date && $s['i_day']){
        $daylist = ReplaceEscapedVars($s['Action_Comments():DayEntry']);
        $s['Comments_DayList'] .= $daylist;
        $s['i_day'] = ''; }

      # Add data of entry to day list only if visibility is set.
      if (0 != $entry['visibility'])
        continue;
      $s['i_old_date'] = $date;   # Update day only for visible entries.
      $s['i_time']     = date('H:i:s', (int) $entry['datetime']);
      $s['i_author']   = EscapeHTML($entry['author']);
      $s['i_title']    = $entry['title'];
      $s['i_id']       = $entry['id'];
      $s['i_day'] .= ReplaceEscapedVars($s['Action_Comments():Entry']);}

  # Either output formatted RC list, or message about its non-existence.
    $s['content'] = $s['Action_Comments():list']; }
  else
    $s['content'] = $s['Action_Comments():NoRecentComments'];
  $s['title']   = $s['Action_Comments():title'];
  OutputHTML(); }

function Comments_AddToRecent($title,$id,$time,$tmp,$path_author,$vis) {
# Add info of comment addition to RecentComments file.
  global $Comments_Recent_path, $nl;
  $author = file_get_contents($path_author);

  # Get old text of RecentComments file, if it exists.
  $Comments_Recent_txt = $nl;
  if (is_file($Comments_Recent_path))
    $Comments_Recent_txt = file_get_contents($Comments_Recent_path);

  # Add new entry to RecentComments file text.
  $add = $vis.$nl.$time.$nl.$author.$nl.$title.$nl.$id.$nl;
  $Comments_Recent_txt = $add.'%%'.$nl.$Comments_Recent_txt;

  # Safe writing of RecentComments file.
  if (is_file($tmp)) {
    file_put_contents($tmp, $Comments_Recent_txt); 
    rename($tmp, $Comments_Recent_path); }
  
  # Clean up.
  unlink($path_author); }

###########################
# Comments administration #
###########################

function Action_CommentsAdmin() {
# Administration menu for comments.
  global $Comments_captcha_path, $Comments_dir, $s;
  $s['title']   = $s['Action_CommentsAdmin():title'];

  # If no $Comments_dir exists, offer to create it.
  if (!is_dir($Comments_dir))
    $s['content'] = $s['Action_CommentsAdmin():Build'];

  # Captcha setting.
  else {
    if (is_file($Comments_captcha_path)) {
      $s['Comments_captcha']=file_get_contents($Comments_captcha_path);
      $s['Comments_Captcha']=$s['Action_CommentsAdmin():YesCaptcha']; }
    else
      $s['Comments_Captcha']=$s['Action_CommentsAdmin():NoCaptcha'];
    $s['content'] = $s['Action_CommentsAdmin():SetCaptcha']; }

  OutputHTML(); }

function PrepareWrite_CommentsDirBuild() {
# Prepare building of comments directory.
  global $Comments_dir, $nl;
  if (is_dir($Comments_dir))
    ErrorFail('Comments_CannotBuildDir');
  else
    $tasks = 'mkdir("'.$Comments_dir.'");'.$nl;
  return $tasks; }

function PrepareWrite_CommentsSetCaptcha() {
# Prepare setting captcha.
  global $Comments_captcha_path, $nl, $s;
  $_POST['new_auth'] = $s['Comments_key'];
  $tasks = PrepareWrite_admin_sets_pw();
  $tmp_captcha = NewTemp($_POST['new_pw']);
  $tasks .= 'if (is_file("'.$tmp_captcha.'")) rename("'.$tmp_captcha.
                                '", "'.$Comments_captcha_path.'");'.$nl; 
  return $tasks; }
