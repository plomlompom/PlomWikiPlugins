<?php
# PlomWiki plugin: Atom
# 
# Provides Atom feeds. (Currently only for the comments of the Comments
# plugin; via Action_AtomComments().)

$s = ReadStringsFile($plugin_strings_dir.'Atom', $s);

$Atom_dir  = $plugin_dir.'Atom/';
$Atom_path = $Atom_dir.'AtomID';

function Action_AtomComments() {
# Output Atom feed of recent comments.
  global $Atom_dir, $Atom_path, $nl, $now, $s, $Comments_dir,
         $Comments_Recent_path;

  if (is_file($Comments_Recent_path)) {
    $s['Atom_Domain']  = $_SERVER['SERVER_NAME'];
    $RootDir           = dirname($_SERVER['REQUEST_URI']);
    $s['Atom_RootURL'] = $s['Atom_Domain'].$RootDir;
                                       
    if (is_file($Atom_path))
      $s['Atom_ID'] = file_get_contents($Atom_path);
    else {
      mkdir($Atom_dir);
      $s['Atom_now'] = date('Y-m-d', (int) $now);
      $s['Atom_ID']  = ReplaceEscapedVars($s['Atom_ID_pattern']);
      file_put_contents($Atom_path, $s['Atom_ID']); }

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
        $s['i_date']     = date('Y-m-d', (int) $line);
        if (!$s['Atom_UpDate'])
          $s['Atom_UpDate'] = $s['i_datetime']; }
      else if (2 == $i)
        $s['i_author']   = EscapeHTML($line);
      else if (3 == $i)
        $s['i_title']    = $line;
      else if (4 == $i)
        $s['i_id']       = $line; }

    $s['design'] = $s['Action_Atom():output']; 
    header('Content-Type: application/atom+xml; charset=utf-8'); }

  else ErrorFail('Atom_NoFeed');

  OutputHTML(); }