<?php
# PlomWiki plugin: Atom
# 
# Provides Atom feeds. (Currently only for the comments of the Comments
# plugin; via Action_AtomComments().)

$s = ReadStringsFile($plugin_strings_dir.'Atom', $s);

function Action_AtomComments() {
# Output Atom feed of recent comments.
  global $nl, $s, $Comments_dir, $Comments_Recent_path;

  if (is_file($Comments_Recent_path)) {
    $s['Atom_RootURL'] = $_SERVER['SERVER_NAME'].
                                       dirname($_SERVER['REQUEST_URI']);
    $max_entries = 20;
    $i_entries   = 0;

    $txt      = file_get_contents($Comments_Recent_path);
    $lines    = explode($nl, $txt);
    foreach ($lines as $line) {
      $i++;
      if ('%%' == $line) {
        $comments = Comments_GetComments($Comments_dir.$s['i_title']);
        $text = Comments_FormatText($comments[$s['i_id']]['text']);
        $s['i_text'] = EscapeHTML($text);
        $s['Atom_Entries'] .= ReplaceEscapedVars($s['Atom_Entry']);
        $i_entries++;
        if ($i_entries == $max_entries)
          break;
        $i = 0; }
      else if (1 == $i) {
        $s['i_datetime'] = date(DATE_ATOM, (int) $line);
        if (!$s['Atom_UpDate'])
          $s['Atom_UpDate'] = $s['i_datetime']; }
      else if (2 == $i)
        $s['i_author']   = EscapeHTML($line);
      else if (3 == $i)
        $s['i_title']    = $line;
      else if (4 == $i)
        $s['i_id']       = $line; }

    $s['design'] = $s['Action_Atom():output']; }

  else ErrorFail('Atom_NoFeed');

  OutputHTML(); }