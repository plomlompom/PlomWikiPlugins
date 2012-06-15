<?php
# PlomWiki plugin: EditConflict. Warn of / resolve editing conflicts.
# 
# Copyright 2010-2012 Christian Heller / <http://www.plomlompom.de/>
# License: AGPLv3 or any later version. See file LICENSE for details.

$s = ReadStringsFile($plugin_strings_dir.'EditConflict', $s);

$hook_before_action .= '
# Hook before page writing action is initiated.
if ($action == "Action_write" && $_GET["t"] == "page") {
  $s["css"] .= $s["css_edit"];
  $s["css"] .= $s["css_history"];

  # Page exists? Get most recent text and its hash.
  if (is_file($page_path)) {
    $s["EditConflict_TxtOther"] = file_get_contents($page_path);
    $hash_recent = hash("md5", $s["EditConflict_TxtOther"]); }

  # Page was deleted while you were editing? Assume "delete" text.
  else {
    $s["EditConflict_TxtOther"] = "delete";
    $hash_recent = ""; }

  # Most recent text and text from which your editing started differ?
  if ($_POST["hash_start"] != $hash_recent) {

    # Sanitize for comparison with sanitized text.
    $_POST["text"] = Sanitize($_POST["text"]);

    # Conflicting edits lead to same result? Inform via error message.
    if ($_POST["text"] == $s["EditConflict_TxtOther"])
      ErrorFail("EditConflict_ErrorSame");

    # Conflicting edit results? Resolve with Action_EditConflict().
    else {
      $s["EditConflict_TxtYours"] = $_POST["text"];
      $action = "Action_EditConflict"; } } }

# Hook before page editing, including edit conflict resolving editing:
# Send hash of text from which editing starts via hidden $_POST input.
if ($action == "Action_page_edit" || $action == "Action_EditConflict") {
  if (is_file($page_path))
    $s["EditConflict_Hash"] = hash("md5", file_get_contents($page_path));
  else
    $s["EditConflict_Hash"] = "";
  $s["Action_page_edit():form_Plugins"].=$s["EditConflict_HashInput"]; }
';

function Action_EditConflict() {
# Show page editing interface to resolving page editing conflict.
  global $nl, $page_path, $s;
  $s['title'] = $s['Action_EditConflict():title'];

  # All need sanitation. For diffing, some are needed un-escaped first.
  # Build edit conflict diff.
  $di=PlomDiff($s["EditConflict_TxtOther"],$s["EditConflict_TxtYours"]);
  foreach (explode($nl, $di) as $line_n => $line) {
    if     ($line[0] == '>')
      $theme = 'Action_page_history():diff_ins';
    elseif ($line[0] == '<')
      $theme = 'Action_page_history():diff_del';
    else
      $theme = 'Action_page_history():diff_meta';
    if ($line[0] == '<' or $line[0] == '>') 
      $line = EscapeHTML(substr($line, 1));
    $s['line'] = $line;
    $s["EditConflict_Diff"] .= ReplaceEscapedVars($s[$theme]); }

  # HTML-escape all strings; sanitize those taken from $_POST.
  $s["EditConflict_TxtYours"] = EscapeHTML($s["EditConflict_TxtYours"]);
  $s["EditConflict_TxtOther"] = EscapeHTML($s["EditConflict_TxtOther"]);
  $s["EditConflict_Author"]   = EscapeHTML(Sanitize($_POST["author"]));
  $s["EditConflict_Summary"]  = EscapeHTML(Sanitize($_POST["summary"]));

  $s['content'] = $s['Action_EditConflict():forms'];
  OutputHTML(); }
