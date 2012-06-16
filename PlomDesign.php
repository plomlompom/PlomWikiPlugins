<?php
# PlomWiki plugin: PlomDesign. Design/layout of <www.plomlompom.de>.
# 
# Copyright 2010-2012 Christian Heller / <http://www.plomlompom.de/>
# License: AGPLv3 or any later version. See file LICENSE for details.

$s = ReadStringsFile($plugin_strings_dir.'PlomDesign', $s);

$hook_before_action .= '
if ($action == "Action_page_view") {
  $s["PlomDesign_css"]   = $s["PlomDesign_css_pageview"];
  $s["css_class"]       .= " pageview"; }
else if ($action == "Action_page_history")
  $s["PlomDesign_css"]  .= $s["css_history"];
else if ($action == "Action_page_edit")
  $s["PlomDesign_css"]  .= $s["css_edit"];
else if ($action == "EditConflict") {
  $s["PlomDesign_css"]  .= $s["css_history"];
  $s["PlomDesign_css"]  .= $s["css_edit"]; }
if (substr($action, 7, 5) !== "page_")
  $s["PlomDesign_ActionLinks"] = "";
if (   $action == "Action_page_Comments_hidden"
    or $action == "Action_page_Comments_mod") {
  $s["css_class"]       .= " Comments";
  $s["PlomDesign_css"]  .= $s["PlomDesign_css_Comments"]; }
';

$hook_ErrorFail .= '
$s["css_class"] .= " fail";
$s["PlomDesign_css"]     = $s["PlomDesign_css_fail"];
if ($msg = "IllegalPageTitle")
  $s["PlomDesign_ActionLinks"] = "";
';
