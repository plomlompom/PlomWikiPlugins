<?php
# PlomWiki plugin: AutoSum
# 
# Provides to the diff summary field an automatic summary of what was
# changed, by excerpting the actual diff text.

# Hook into WritePage() to fill up an empty $summary with AutoSum().
$hook_WritePage_diff .= 'if (!$summary) $summary = AutoSum($diff_add);';

function AutoSum($diff_add)
# Build nice short single-line summary of diff from diff text provided.
{ global $nl;
  $diff_lines = explode($nl, $diff_add); 

  # Count "<" and ">" lines.
  $n_add = 0; $n_del = 0;
  foreach ($diff_lines as $line)
  { if     ('>' == $line[0]) $n_add++;
    elseif ('<' == $line[0]) $n_del++; }

  # Build summary of ">" lines.
  if (0 < $n_add)
  { $sep_to_minus = ' ';
    $prefix_add   = '[+]';
    foreach ($diff_lines as $line)
    { if ('>' == $line[0])
      { $string_add .= $sep.substr($line, 1); 
        $sep         = ' / '; } 
      else
        $sep         = ' […] '; } }
  $string_add = substr($string_add, 6);

  # Build summary of "<" lines.
  if (0 < $n_del)
  { $prefix_del = '[-]';
    foreach ($diff_lines as $line)
    { if ('<' == $line[0])
      { $string_del .= $sep.substr($line, 1); 
        $sep         = ' / '; } 
      else
        $sep         = ' […] '; } }
  $string_del = substr($string_del, 6);

  # Build summary string by adding everything; reduce to 100 UTF8 chars.
  $summary = 'AutoSum: '.$prefix_add.$string_add.$sep_to_minus.
                                                $prefix_del.$string_del;
  if (100 < strlen($summary))
    $summary = mb_substr($summary, 0, 99, 'UTF-8').'…';
  
  return $summary; }
