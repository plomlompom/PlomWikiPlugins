<?php
# PlomWiki plugin: PwPages
# 
# Provides page passwords, to be set by admin via Action_page_PwPages().
#
# To do: how to /remove/ page passwords.

$s = ReadStringsFile($plugin_strings_dir.'PwPages', $s);

# Extend $legal_pw_key keys to page titles prefixed with 'PwPages_'.
$PwPages_prefix               = 'PwPages_';
$legal_pw_key                .= '|'.$PwPages_prefix.$legal_title;

# Authorize as key for "t=page" current page title, 'PwPages_'-prefixed.
$s['PwPages_CurKey']          = $PwPages_prefix.$title;
$permissions['page'][]        = $s['PwPages_CurKey'];

# Replace Action_page_edit() form with one allowing page passwords.
$s['Action_page_edit():form'] = $s['PwPages_ErsatzForm'];

function Action_page_PwPages()
# Output form to set password for current page via admin authorization.
{ global $s;
  $s['title']   = $s['Action_PwPages():title']; 
  $s['content'] = $s['Action_PwPages():form'];
  OutputHTML(); }
