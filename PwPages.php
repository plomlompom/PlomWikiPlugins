<?php

# PlomWiki plugin "PwPages"
# Provides page passwords; Action_page_set_pw()

$l['SetPagePW'] = 'Set page password';
$hook_Action_page_edit .= '$form = BuildPostForm($title_url.\'&amp;action=write'
                             .'&amp;t=page\', $input, $esc.\'PWfor\'.$esc.\' <'.
                          'select name="auth"><option value="*">\'.$esc.\'admin'
                          .'\'.$esc.\'</option><option value="\'.$title.\'">\''.
                            '.$esc.\'page\'.$esc.\'</option></select>: <input '.
                                               'type="password" name="pw">\');';
$permissions['page'][] = $title;

$l['PWfor'] = 'Password for';
$l['page'] = 'page';
function Action_page_set_pw()
{ global $esc, $title;
  ChangePW_form($esc.'page'.$esc.' "'.$title.'"', $title); }

function ChangePW_form($desc_new_pw, $new_auth, $desc_pw = 'Admin', 
                       $auth = '*', $t = 'admin_sets_pw')
# Output page for changing password keyed to $auth and described by $desc.
{ global $esc, $l, $nl, $nl2, $title_url;
  $input = $esc.'NewPWfor'.$esc.' '.$desc_new_pw.':<br />'.$nl.
           '<input type="hidden" name="new_auth" value="'.$new_auth.'">'.$nl
          .'<input type="password" name="new_pw" /><br />'.$nl.
           '<input type="hidden" name="auth" value="'.$auth.'">'.$nl.
           $desc_pw.' '.$esc.'pw'.$esc.':<br />'.$nl.
           '<input type="password" name="pw">';
  $form = BuildPostForm($title_url.'&amp;action=write&amp;t='.$t, $input, '');
  $l['title'] = $esc.'ChangePWfor'.$esc.' '.$desc_new_pw; $l['content'] = $form;
  OutputHTML(); }
