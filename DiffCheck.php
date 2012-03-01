<?php
# PlomWiki plugin: DiffCheck
#
# Some diagnostic functions to check that Diff works correctly and that
# page diff histories are not corrupted. Call by ?action=DiffCheck_All.

function Action_DiffCheck_All() {
# Apply all DiffCheck function to all pages, output results.
  global $nl;
  echo 'DiffCheck all pages:<br />'.$nl.$nl.'Working ...<br />'.$nl.$nl;
  $titles = GetAllPageTitles();
  foreach ($titles as $title) {
    $results['EmptyFirst'] = DiffCheck_EmptyFirst($title);
    $results['EmptyDiffs'] = DiffCheck_EmptyDiffs($title);
    $results['BrokenLine'] = DiffCheck_BrokenLine($title); 
    $results['BadStart']   = DiffCheck_BadStart($title); 
    $results['Chronology'] = DiffCheck_Chronology($title); 
    foreach ($results as $what => $result) {
      if ($result == ':-(') echo $title.': '.$what.'<br />'.$nl; } } 
  echo 'Done! Above: list of problems found. If empty, you\' re lucky!';
  exit(); }

function DiffCheck_EmptyFirst($title) {
# Check if first diff text is empty.
global $diff_dir;
  $diff_path = $diff_dir.$title;

  $diff_list_reversed = array_reverse(DiffList($diff_path));
  if (empty($diff_list_reversed[0]['text'])) return ':-(';
  else                                       return ':-)'; }

function DiffCheck_EmptyDiffs($title) {
# Check if history contains empty diff texts.
  global $diff_dir;
  $diff_path = $diff_dir.$title;

  $diff_list = DiffList($diff_path);
  $result = ':-)';
  foreach ($diff_list as $diff_data)
    if (empty($diff_data['text'])) {
      $result = ':-(';
      break; }
  return $result; }

function DiffCheck_BrokenLine($title) {
# Compare current text against result of computing back diff history to
# the beginning and rebuilding history until newest version again.
#
# Use the lines commented out to analyze history in detail.
  global $diff_dir, $pages_dir;
  $page_path = $pages_dir.$title;
  $diff_path = $diff_dir.$title;

  $diff_list = DiffList($diff_path);
  $text = $text_original = file_get_contents($page_path);

  /* echo '[[['."\n".$text."\n".']]]'."\n"; */

  foreach ($diff_list as $diff_data) {
    $diff          = $diff_data['text'];
    /* echo '%%%'."\n".$diff."\n".'%%%'."\n"; */
    $reversed_diff = PlomDiffReverse($diff);
    $text          = PlomPatch($text, $reversed_diff);
    /* echo '[[['."\n".$text."\n".']]]'."\n"; */ }

    /* echo '[[[-----------------------------------------]]]'."\n"; */

  $diff_list_reversed = array_reverse($diff_list);
  foreach ($diff_list_reversed as $diff_data) {
    $diff = $diff_data['text'];
    /* echo '%%%'."\n".$diff."\n".'%%%'."\n"; */
    $text = PlomPatch($text, $diff); 
    /* echo '[[['."\n".$text."\n".']]]'."\n"; */ }

  if ($text !== $text_original) return ':-(';
  else                          return ':-)'; }

function DiffCheck_BadStart($title) {
# Reverse page text diff by text to beginning, check its emptiness.
  global $diff_dir, $pages_dir;
  $page_path = $pages_dir.$title;
  $diff_path = $diff_dir.$title;

  $diff_list = DiffList($diff_path);
  $text = file_get_contents($page_path);
  foreach ($diff_list as $diff_data) {
    $diff          = $diff_data['text'];
    $reversed_diff = PlomDiffReverse($diff);
    $text          = PlomPatch($text, $reversed_diff); }
  if ($text !== '') return ':-(';
  else              return ':-)'; }

function DiffCheck_Chronology($title) {
# Check if 
  global $diff_dir, $pages_dir;
  $page_path = $pages_dir.$title;
  $diff_path = $diff_dir.$title;

  $diff_list = DiffList($diff_path);
  $result = ':-)';
  $last_date = 0;
  $diff_list = array_reverse($diff_list);
  foreach ($diff_list as $diff_data)
    if ($diff_data['time'] < $last_date) {
      $result = ':-(';
      break; }
    else
      $last_date = $diff_data['time'];
  return $result;  }