<?php

$s = ReadStringsFile($plugin_strings_dir.'PlomDesign', $s);

$hook_before_action .= '
if (substr($action, 7, 5) !== "page_")
  $s["ActionLinks_page"] = "";
if ($action == "Action_page_view") {
  if (is_file($page_path))
    $s["css_class"]        = "default article";
  $s["design"]          .= Comments();
  $s["design"]          .= Autolink_Backlinks(); }
if ($action == "Action_page_edit") { 
  $s["css_class"]        = "edit default"; }
if ($action == "Action_page_history")
  $s["css_class"]        = "default history";
';

$hook_ErrorFail .= '
$s["css_class"] = "default fail";
';