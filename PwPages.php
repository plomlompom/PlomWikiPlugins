<?php
# PlomWiki plugin: PwPages
# 
# Provides page passwords, to be set by admin via Action_PwPages().
#
# To do: how to /remove/ page passwords.

# PwPages-specific language-variable phrases.
$l['PwPages_PWfor']     = 'Password for';
$l['PwPages_page']      = 'page';
$l['PwPages_set']       = 'Set password for page';

# Extend $legal_pw_key keys to page titles prefixed with 'PwPages_'.
$PwPages_prefix         = 'PwPages_';
$legal_pw_key          .= '|'.$PwPages_prefix.$legal_title;

# Authorize as key for "t=page" the current page title prefixed with 'PwPages_'.
$PwPages_CurKey         = $PwPages_prefix.$title;
$permissions['page'][]  = $PwPages_CurKey;

# Replace Action_page_edit() form with one allowing page passwords.
$hook_Action_page_edit .= '
global $PwPages_CurKey;
$form = \'<form method="post" \'.
                 \'action="\'.$title_url.\'&amp;action=write&amp;t=page">\'.$nl.
        \'<textarea name="text" \'.
              \'rows="\'.$esc.\'Action_page_edit_TextareaRows\'.$esc.\'">\'.$nl.
        $text.\'</textarea>\'.$nl.
        $esc.\'Author\'.$esc.\': <input name="author" type="text" />\'.$nl.
        $esc.\'Summary\'.$esc.\': <input name="summary" type="text" />\'.$nl.
        $esc.\'PwPages_PWfor\'.$esc.\' <select name="auth">\'.$nl.
        \'<option value="*">\'.$esc.\'admin\'.$esc.\'</option>\'.$nl.
        \'<option value="\'.$PwPages_CurKey.\'">\'.
                                   $esc.\'PwPages_page\'.$esc.\'</option>\'.$nl.
        \'</select>: <input type="password" name="pw">\'.$nl.
        \'<input type="submit" value="OK" />\'.$nl.
        \'</form>\';';

function Action_PwPages()
# Output form to set password for current page via admin authorization.
{ global $esc, $l, $nl, $PwPages_CurKey, $PwPages_prefix, $title, $title_url;
  $form = '<form method="post" '.
            'action="'.$title_url.'&amp;action=write&amp;t=admin_sets_pw">'.$nl.
          $esc.'PwPages_set'.$esc.' "'.$title.'":<br />'.$nl.
          '<input type="hidden"   name="new_auth" '.
                                             'value="'.$PwPages_CurKey.'">'.$nl.
          '<input type="password" name="new_pw" /><br />'.$nl.
          '<input type="hidden"   name="auth"     value="*">'.$nl.
          'Admin '.$esc.'pw'.$esc.':<br />'.$nl.
          '<input type="password" name="pw">'.$nl.
          '<input type="submit"   value="OK" />'.$nl.
          '</form>';
  $l['title']   = $esc.'PwPages_set'.$esc.' "'.$title.'"';
  $l['content'] = $form;
  OutputHTML(); }
