<?php
# PlomWiki plugin: PwPages. Use, set (via action=page_PwPages) page PWs.
# 
# Copyright 2010-2012 Christian Heller / <http://www.plomlompom.de/>
# License: AGPLv3 or any later version. See file LICENSE for details.
#
# To do: how to /remove/ page passwords.

$s = ReadStringsFile($plugin_strings_dir.'PwPages', $s);

$s['ActionLinks_page_Plugins'] .= $s['PwPages_ActionLinks'];

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
