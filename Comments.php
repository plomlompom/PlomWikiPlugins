<?php
# PlomWiki plugin: Comments
# 
# Provides comments; Action_Comments_admin(), Action_Comments()

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

#########################
# Most commonly called. #
#########################

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
    foreach ($comment_list as $id => $x) {
      $s['i_id']       = $id;
      $s['i_datetime'] = date('Y-m-d H:i:s', (int) $x['datetime']);
      $s['i_author']   = $x['author'];
      $s['i_url']      = $x['url'];
      if ($s['i_url'])
        $s['i_author'] = ReplaceEscapedVars($s['Comments_AuthorURL']);
      $s['i_text']     = Comments_FormatText($x['text']);
      $s['Comments():Comments'] .= ReplaceEscapedVars(
                                          $s['Comments():Comment']); }
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

function Action_page_Comments_hidden() {
# Show the hidden comments for a page.
  global $Comments_dir, $pages_dir, $s;
  $cur_page_file = $Comments_dir.$s['page_title'];

  # Silently fail if $Comments_dir or page do not exist.
  if (!is_dir($Comments_dir) or !is_file($pages_dir.$s['page_title'])
      or !is_file($cur_page_file)) {
    $s['Comments_None'] = $s['Comments_NoneHidden'];
    $s['content']       = $s['Comments():None']; }

  if (is_file($cur_page_file)) {
    $comment_list = Comments_GetComments($cur_page_file, 1);
    foreach ($comment_list as $id => $x) {
      $s['i_id']       = $id;
      $s['i_datetime'] = date('Y-m-d H:i:s', (int) $x['datetime']);
      $s['i_author']   = $x['author'];
      $s['i_url']      = $x['url'];
      if ($s['i_url'])
        $s['i_author'] = ReplaceEscapedVars($s['Comments_AuthorURL']);
      $s['i_text']     = Comments_FormatText($x['text']);
      $s['content'] .= ReplaceEscapedVars($s['Comments():Comment']); } }

  $s['title'] = $s['Action_page_Comments_hidden:title'];
  OutputHTML(); }

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

function Comments_GetComments($comment_file, $show = 0, &$ignored=NULL ) {
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
      if ($line_n == 0 and $show != $line) {     # Break loop and set
          $ignore = 1;                           # $ignore if visibility
          $ignored++;                            # value is wrong.
          break; }                               # 
      elseif ($line_n==1)           $id              = $line;
      elseif ($line_n==2)           $datetime        = $line;
      elseif ($line_n==3)           $author          = $line;
      elseif ($line_n==4 and $line) $url             = substr($line,1);
      else                          $lines_comment[] = substr($line,1);}
    if (1 == $ignore)                      # Ignore entry if $ignore was
      continue;                            # was visibility value check.
    $comments[$id]['author']   = $author;
    $comments[$id]['datetime'] = $datetime;
    $comments[$id]['url']      = $url;
    $comments[$id]['text']     = implode($nl, $lines_comment); }

  return $comments; }

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
  # Prefix ':' to lines that could be empty, as empty lines = separator.
  $lines = explode($nl, $text);
  $text = ':'.implode($nl.':', $lines);
  $time = time();
  $add = '0'.$nl.$new_id.$nl.$time.$nl.$author.$nl.':'.$url.$nl.$text.
                                                                $nl.$nl;

  # Return tasks for writing/update of Comments/RecentComments files.
  $tmp_Comms       = NewTemp($old.$add);
  $tmp_AddToRecent = NewTemp();
  $tmp_author      = NewTemp($author);
  return 'if (is_file("'.$tmp_Comms.'")) rename("'.$tmp_Comms.'", "'
                                              .$cur_page_file.'");'.$nl.
           'Comments_AddToRecent("'.$s['page_title'].'", '.$new_id.', '.
             $time.', "'.$tmp_AddToRecent.'", "'.$tmp_author.'", 0);'; }

###################
# Recent Comments #
###################

function Action_Comments() {
# Provide HTML output of RecentComments file.
  global $nl, $s, $Comments_Recent_path;

  # Format RecentComments file content into HTML output.
  if (is_file($Comments_Recent_path)) {
    $txt      = file_get_contents($Comments_Recent_path);
    $lines    = explode($nl, $txt);
    
    # Count lines of each entry in RC file, starting anew at '%%'.
    foreach ($lines as $line) {
      $i++;
      if ('%%' == $line) {
        $i      = 0;
        $ignore = FALSE; }

      # Trigger ignorance of entry depending on visibility value.
      elseif (1 == $i) {
        if (0 != $line)
          $ignore = TRUE; }

      # From 1st line, get date and time. If new date, finalize / output
      # previous day's entry list. Don't count the new date of the 1st
      # entry as new. The empty last line of RC file will be handled as
      # a date line too, triggering one last "date changed" event.
      elseif (2 == $i) {
        $datetime                      = date('Y-m-d H:i:s',(int)$line);
        list($s['i_date'],$s['i_time'])= explode(' ', $datetime);
        if ($s['i_old_date'] and $s['i_old_date'] != $s['i_date']
            and $s['i_day']) {
          $s['Comments_DayList'] .= ReplaceEscapedVars(
                                      $s['Action_Comments():DayEntry']);
          $s['i_day'] = ''; }
        if (!$ignore)
          $s['i_old_date'] = $s['i_date']; }

      # Harvest remaining data from lines 2 and 3.
      elseif (3 == $i and !$ignore)
        $s['i_author'] = $line;
      elseif (4 == $i and !$ignore)
        $s['i_title']  = $line;
        
      # After reaching 4th line, build whole list entry for this diff.
      elseif (5 == $i and !$ignore) {
        $s['i_id']   = $line;
        $s['i_day'] .= ReplaceEscapedVars(
                                     $s['Action_Comments():Entry']); } }

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
