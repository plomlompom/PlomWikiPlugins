<?php

$s = ReadStringsFile($plugin_strings_dir.'PlomDesign', $s);

$hook_before_action .= '
if ($action == "Action_page_view") {
  $s["design"]    .= Comments();
  $s["design"]    .= Autolink_Backlinks();
  $s["css"]        = $s["css_pageview"];
  $s["css_class"] .= " pageview"; }
else if ($action == "Action_page_history")
  $s["css"]         .= $s["css_history"];
else if ($action == "Action_page_edit")
  $s["css"]         .= $s["css_edit"];
else if (substr($action, 7, 5) !== "page_")
  $s["ActionLinks_page"] = "";
if ($action == "Action_page_Comments_hidden")
  $s["css"] .= $s["css_hidden_comments"];
';

$hook_ErrorFail .= '
$s["css_class"] .= " fail";
$s["css"]        = $s["css_fail"];
if ($msg = "IllegalPageTitle")
  $s["ActionLinks_page"] = "";
';
