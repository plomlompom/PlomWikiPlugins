<?php
# PlomWiki plugin: Comments
# 
# Provides comments; Action_Comments_admin(), Action_Comments()

# Language- and formatting-specific variables.
$l['Comments'] = 'Comments';
$l['Comments_URL'] = 'Your URL';
$l['Comments_Name'] = 'Your name';
$l['Comments_None'] = 'No one commented on this page yet.';
$l['Comments_Admin'] = 'Comments administration';
$l['Comments_NoDir'] = 'Comments directory not yet built.';
$l['Comments_Write'] = 'Write your own comment';
$l['Comments_NoText'] = 'No comment written.';
$l['Comments_Recent'] = 'Recent comments';
$l['Comments_URLMax'] = 'URL must not exceed length (characters/bytes)';
$l['Comments_TextMax'] = 'Text must not exceed length (characters/bytes)';
$l['Comments_WriteNo'] = 'Commenting currently impossible: Captcha not set.';
$l['Comments_BuildDir'] = 'Build comments directory.';
$l['Comments_NoAuthor'] = 'Author field empty.';
$l['Comments_RecentNo'] = 'No RecentComments file found.';
$l['Comments_AuthorMax'] = 'Author name must not exceed length (chars/bytes)';
$l['Comments_AskCaptcha'] = 'Captcha password needed! Write';
$l['Comments_CurCaptcha'] = 'Current captcha';
$l['Comments_InvalidURL'] = 'Invalid URL format.';
$l['Comments_NewCaptcha'] = 'Set new captcha';
$l['Comments_NoBuildDir'] = 'Do not build comments directory.';
$l['Comments_NoCurCaptcha'] = 'No captcha set yet.';
$l['Comments_TextareaRows'] = 10;
$l['Comments_TextareaCols'] = 40;
$l['Comments_CannotBuildDir'] = 'Cannot build Comments directory: '.
                                'already exists.';
$l['Comments_NewCaptchaExplain'] = 'Write "delete" to unset captcha. '.
                                   'Commenting won\'t be possible then.';

$Comments_dir              = $plugin_dir.'Comments/';
$Comments_captcha_path     = $Comments_dir.'_captcha';
$Comments_Recent_path      = $Comments_dir.'_RecentComments';

$Comments_key              = '_comments_captcha';
$legal_pw_key             .= '|'.$Comments_key;
$permissions['Comments'][] = $Comments_key;

#########################
# Most commonly called. #
#########################

function Comments()
# Return display of page comments and commenting form.
{ global $Comments_captcha_path, $Comments_dir, $Comments_key, $esc, $nl, $nl2,
         $pages_dir, $title, $title_url;

  # Silently fail if $Comments_dir or page do not exist.
  if (!is_dir($Comments_dir) or !is_file($pages_dir.$title))
    return;

  # Build/format $comments display (or, if no comments, show a message on that).
  $cur_page_file = $Comments_dir.$title;
  if (is_file($cur_page_file))
  { $comment_list = Comments_GetComments($cur_page_file);
    foreach ($comment_list as $id => $x)
    { $datetime         = date('Y-m-d H:i:s', (int) $x['datetime']);
      $author           = '<strong>'.$x['author'].'</strong>';
      $url              = $x['url'];
      if ($url) $author = '<a href="'.$url.'">'.$author.'</a>';
      $comment_text     = Comments_FormatText($x['text']);
      $comments .= $nl2.
                   '<article id="comment_'.$id.'">'.
                      '<header class="Comments_head">'.
                         '<a href="#comment_'.$id.'">#'.$id.'</a></header>'.$nl.
                   '<div class="Comments_body">'.$comment_text.'</div>'.$nl.
                   '<footer class="Comments_foot">'.$author.' / '.$datetime.
                                                      '</footer></article>'; } }
  if (!$comments)
    $comments = $nl2.'<p>'.$esc.'Comments_None'.$esc.'</p>';

  # Commenting $form. Allow commenting if $Comments_captcha_path file exists.
  $write   = '<h2>'.$esc.'Comments_Write'.$esc.'</h2>'.$nl2;
  if (is_file($Comments_captcha_path))
  { $captcha = file_get_contents($Comments_captcha_path);
    $form = '<form method="post"
                  action="'.$title_url.'&amp;action=write&amp;t=Comments">'.$nl.
            $esc.'Comments_Name'.$esc.': '.
                 '<input class="Comments_InputName" name="author" /><br />'.$nl.
            $esc.'Comments_URL'.$esc.': '.
                           '<input class="Comments_InputURL" name="URL" />'.$nl.
            '<pre><textarea name="text" class="Comments_Textarea" '.
                               'rows="'.$esc.'Comments_TextareaRows'.$esc.'" '.
                            'cols="'.$esc.'Comments_TextareaCols'.$esc.'">'.$nl.
            $text.'</textarea></pre>'.$nl.
            $esc.'Comments_AskCaptcha'.$esc.' "'.$captcha.'": '.
                   '<input name="pw" class="Comments_InputCaptcha" size="5" />'.
                   '<input name="auth" type="hidden" '.
                                          'value='.'"'.$Comments_key.'" />'.$nl.
            '<input type="submit" value="OK" />'.$nl.
            '</form>';
    $write .= $form; }
  else
    $write .= '<p>'.$esc.'Comments_WriteNo'.$esc.'</p>';

  # Finally, put everything together.
  return $nl2.'<h2>'.$esc.'Comments'.$esc.'</h2>'.$comments.$nl2.$write; }

function Comments_FormatText($text)
# Comment formatting: EscapeHTML, paragraphing / line breaks.
{ global $nl;
  $text      = EscapeHTML($text);
  $lines     = explode($nl, $text);
  $last_line = '';
  foreach ($lines as $n => $line)
  { if     (''  == $last_line and '' !== $line) $lines[$n] = '<p>'.$line;
    elseif ('' !== $last_line and ''  == $line) $lines[$n] = '</p>'.$nl;
    elseif ('' !== $last_line and '' !== $line) $lines[$n] = '<br />'.$nl.$line;
    $last_line = $line; }
  $text = implode($lines);
  if ('</p>' == substr($text, -4)) $text = $text.'</p>';
  return $text; }

function Comments_GetComments($comment_file)
# Read $comment_file into more structured, readable array $comments.
{ global $esc, $nl;
  $comments = array();

  # Read comment info line by line,assume first lines each entry to be metadata.
  $file_txt = file_get_contents($comment_file);
  foreach (explode($esc.$nl, $file_txt) as $entry_txt)
  { if (!$entry_txt)
      continue;
    $time = ''; $author = ''; $url = ''; $lines_comment = array();
    foreach (explode($nl, $entry_txt) as $line_n => $line)
    { if     ($line_n == 0)              $id              = $line;
      elseif ($line_n == 1)              $datetime        = $line;
      elseif ($line_n == 2)              $author          = $line;
      elseif ($line_n == 3) { if ($line) $url             = $line; }
      else                               $lines_comment[] = $line; }
    $comments[$id]['date']     = $date;
    $comments[$id]['author']   = $author;
    $comments[$id]['datetime'] = $datetime;
    $comments[$id]['url']      = $url;
    $comments[$id]['text']     = implode($nl, $lines_comment); }

  return $comments; }

function PrepareWrite_Comments(&$redir)
# Deliver to Action_write() all information needed for comment submission.
{ global $Comments_dir, $esc, $nl, $title, $title_url, $todo_urgent;
  $author = $_POST['author']; $url = $_POST['URL']; $text = $_POST['text'];

  # Repair problematical characters in submitted texts.
  foreach (array('author', 'url', 'text') as $variable_name)
    $$variable_name = Sanitize($$variable_name);
  $author = str_replace("\xE2\x80\xAE", '', $author); # Unicode:ForceRightToLeft

  # Check for failure conditions: empty variables, too large or bad values.
  if (!$author) ErrorFail($esc.'Comments_NoAuthor'.$esc);
  if (!$text)   ErrorFail($esc.'Comments_NoText'.$esc);
  $max_length_url = 2048; $max_length_author = 1000; $max_length_text = 10000;
  if (strlen($author) > $max_length_author)
    ErrorFail($esc.'Comments_AuthorMax'.$esc.': '.$max_length_author);
  if (strlen($url) > $max_length_url)
    ErrorFail($esc.'Comments_URLMax'.$esc.': '.$max_length_url);
  if (strlen($text) > $max_length_text)
    ErrorFail($esc.'Comments_TextMax'.$esc.': '.$max_length_text);
  $legal_url = '[A-Za-z][A-Za-z0-9\+\.\-]*:([A-Za-z0-9\.\-_~:/\?#\[\]@!\$&\'\('.
               '\)\*\+,;=]|%[A-Fa-f0-9]{2})+'; # Thx to @erlehmann
  if ($url and !preg_match('{^'.$legal_url.'$}', $url))
    ErrorFail($esc.'Comments_InvalidURL'.$esc);

  # Collect from $cur_page_file $old text and $highest_id, to top with $new_id.
  $cur_page_file = $Comments_dir.$title;
  $highest_id    = -1;
  if (is_file($cur_page_file))
  { $old = file_get_contents($cur_page_file);
    $previous_comments = Comments_GetComments($cur_page_file);
    foreach ($previous_comments as $id => $stuff)
      if ($id > $highest_id)
        $highest_id = $id; }
  $new_id = $highest_id + 1;
  $redir = $title_url.'#comment_'.$new_id;

  # Put everything together into $add, add $old to get new comments file text.
  $timestamp = time();
  $add = $new_id.$nl.$timestamp.$nl.$author.$nl.$url.$nl.$text.$nl.$esc.$nl;

  # Return tasks for safe writing/update of Comments/RecentComments files.
  $tmp_Comms = NewTemp($old.$add);
  $tmp_AddToRecent = NewTemp();
  $tmp_author = NewTemp($author);
  return 'if (is_file("'.$tmp_Comms.'")) rename("'.$tmp_Comms.'", "'
                                                      .$cur_page_file.'");'.$nl.
         'Comments_AddToRecent("'.$title.'", '.$new_id.', '.$timestamp.', "'.
                                    $tmp_AddToRecent.'", "'.$tmp_author.'");'; }

###################
# Recent Comments #
###################

function Action_Comments()
# Provide HTML output of RecentComments file.
{ global $esc, $l, $Comments_Recent_path, $nl, $title_root;

  $output = '';
  if (is_file($Comments_Recent_path))
  { $txt      = file_get_contents($Comments_Recent_path);
    $lines    = explode($nl, $txt);
    $i        = 0;
    $date_old = '';
    foreach ($lines as $line)
    { $i++;
      if ('%%' == $line)
        $i = 0;
      elseif (1 == $i) 
      { $datetime   = date('Y-m-d H:i:s', (int) $line);
        list($date, $time) = explode(' ', $datetime); }
      elseif (2 == $i)
        $author = $line;
      elseif (3 == $i)
        $title = $line;
      elseif (4 == $i)
      { $id = $line;
        $string = '               <li>'.$time.': '.$author.' <a href="'.
                  $title_root.$title.'#comment_'.$id.'">on '.$title.'</a></li>';
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
    $output = '<p>'.$esc.'Comments_RecentNo'.$esc.'</p>';

  $l['title']   = $esc.'Comments_Recent'.$esc;
  $l['content'] = $output;
  OutputHTML(); }

function Comments_AddToRecent($title, $id, $timestamp, $tmp, $path_author)
# Add info of comment addition to RecentComments file.
{ global $Comments_Recent_path, $nl;
  $author = file_get_contents($path_author);

  # Get old text of RecentComments file, if it exists.
  $Comments_Recent_txt = '';
  if (is_file($Comments_Recent_path))
    $Comments_Recent_txt = file_get_contents($Comments_Recent_path);

  # Add new entry to RecentComments file text.
  $add = $timestamp.$nl.$author.$nl.$title.$nl.$id.$nl;
  $Comments_Recent_txt = $add.'%%'.$nl.$Comments_Recent_txt;

  # Safe writing of RecentComments file.
  if (is_file($tmp))
  { file_put_contents($tmp, $Comments_Recent_txt); 
    rename($tmp, $Comments_Recent_path); }
  
  # Clean up.
  unlink($path_author); }

###########################
# Comments administration #
###########################

function Action_Comments_admin()
# Administration menu for comments.
{ global $Comments_captcha_path, $Comments_dir, $esc, $l, $nl, $nl2, $root_rel;

  # If no $Comments_dir, offer creating it.
  $build_dir = '';
  if (!is_dir($Comments_dir))
    $build_dir = '<p>'.$nl.
                 '<strong>'.$esc.'Comments_NoDir'.$esc.'</strong><br />'.$nl.
                 '<input type="radio" name="build_dir" value="yes" checked='.
                          '"checked">'.$esc.'Comments_BuildDir'.$esc.'<br>'.$nl.
                 '<input type="radio" name="build_dir" value="no">'.$esc.
                                          'Comments_NoBuildDir'.$esc.'<br>'.$nl.
                 '</p>'.$nl;

  # Captcha setting.
  if (is_file($Comments_captcha_path))
    $cur_captcha = $esc.'Comments_CurCaptcha'.$esc.': "'.
                   file_get_contents($Comments_captcha_path).'".';
  else
    $cur_captcha = $esc.'Comments_NoCurCaptcha'.$esc;
  $captcha = '<p><strong>'.$cur_captcha.'</strong></p>'.$nl.
             '<p>'.$esc.'Comments_NewCaptcha'.$esc.': <input name="captcha" />'.
                        ' ('.$esc.'Comments_NewCaptchaExplain'.$esc.')</p>'.$nl;

  # Final HTML.
  $form = '<form method="post" '.
                'action="'.$root_rel.'?action=write&amp;t=Comments_admin">'.$nl.
          $build_dir.$captcha.$nl.
          'Admin '.$esc.'pw'.$esc.': <input name="pw" type="password" />'.
                            '<input name="auth" type="hidden" value="*" />'.$nl.
          '<input type="submit" value="OK" />'.$nl.
          '</form>';
  $l['title'] = $esc.'Comments_Admin'.$esc;
  $l['content'] = $form;
  OutputHTML(); }
  
function PrepareWrite_Comments_admin(&$redir)
# Return to Action_write() all information needed for comments administration.
{ global $Comments_captcha_path, $Comments_dir, $Comments_key, $esc, $nl, 
         $pw_path, $root_rel, $title_url, $todo_urgent;
  $new_pw    = $_POST['captcha'];
  $build_dir = $_POST['build_dir'];

  # Directory building.
  if ($build_dir == 'yes')
  { if (is_dir($Comments_dir))
      ErrorFail($esc.'Comments_CannotBuildDir'.$esc);
    else
      $tasks = 'mkdir("'.$Comments_dir.'");'.$nl; }

  # If $new_pw is "delete", unset captcha. Else, $new_pw becomes new captcha.
  if ($new_pw)
  { $passwords    = ReadPasswordList($pw_path);
    $salt         = $passwords['$salt'];
    $pw_file_text = $salt.$nl;
    if ('delete' == $new_pw) 
    { unset($passwords[$Comments_key]);
      if (is_file($Comments_captcha_path))
        $tasks .= 'unlink("'.$Comments_captcha_path.'");'.$nl; }
    else
    { $passwords[$Comments_key] = hash('sha512', $salt.$new_pw);
      $tmp_captcha = NewTemp($new_pw);
      $tasks .= 'if (is_file("'.$tmp_captcha.'")) rename("'.$tmp_captcha.'", '.
                                         '"'.$Comments_captcha_path.'");'.$nl; }
    foreach ($passwords as $key => $pw)
      $pw_file_text .= $key.':'.$pw.$nl;
    $tmp_pws = NewTemp($pw_file_text);
    $tasks .= 'if (is_file("'.$tmp_pws.'")) rename("'.$tmp_pws.'", '.
                                                           '"'.$pw_path.'");'; }
  return $tasks; }
