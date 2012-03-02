<?php
# PlomWiki plugin: Autolink
#
# Provides autolinks; Action_Autolink_admin()

$s = ReadStringsFile($plugin_strings_dir.'AutoLink', $s);

$s['ActionLinks_Plugins'] .= $s['Autolink_ActionLinks'];

# Directory for Autolink DB.
$Autolink_dir = $plugin_dir.'Autolink/';

# Hook into WritePage(). Autolink_Update() expects the text of the
# newest diff, which WritePage() does not guarantee to make available;
# if in need, read it in from the file system (at this point of
# WritePage() already updated by it).
$hook_WritePage .= '
if (!$diff_add) {
  $diffs          = DiffList($diff_dir.$title);
  $id             = count($diffs) - 1;
  $diff_add       = $diffs[$id][\'text\']; }
$txt_PluginsTodo .= $nl.Autolink_Update($title, $text, $diff_add);';

# Autolink display toggling.
$s['ActionLinks_page_Plugins'] .= $s['Autolink_ActionLinks_page'];
$s['Autolink_show_neg'] = 'yes';
if ('yes' == $_GET['Autolink_show'])
  $s['Autolink_show_neg'] = 'no'; 

##########
# Markup #
##########

function Autolink_Markup($text) {
# Autolink $text according to its Autolink file.
  global $Autolink_dir, $title;

  # Autolink display toggling.
  if ('yes' !== $_GET['Autolink_show'])
    return $text;

  # Don't do anything if there's no Autolink file for the page displayed.
  $cur_page_file = $Autolink_dir.$title;
  if (!is_file($cur_page_file))
    return $text; 
  
  # Get $links_out from $cur_page_file, turn into regex from their files
  $links_out = Autolink_GetFromFileLine($cur_page_file, 1, TRUE);
  foreach ($links_out as $pagename) {
    $regex_pagename = Autolink_RetrieveRegexForTitle($pagename);
    
    # Build autolinks into $text where $avoid applies. Note that even
    # though the regex of $pagename is used for finding strings to
    # autolink, SetLink() may decide to link that string to another
    # title from $titles, a list generated from the matches of
    # $regex_pagename on the other titles of $links_out.
    $avoid  = '(?=[^>]*($|<(?!\/(a|script))))';
    $match  = '/('.$regex_pagename.')'.$avoid.'/ieu';
    $titles = array();
    foreach ($links_out as $x)
      if (preg_match('/'.$regex_pagename.'/ieu', $x))
        $titles[] = $x;
    $repl   = 'Autolink_SetLink("$1", $titles)';
    $text   = preg_replace($match, $repl, $text); }

  return $text; }

function Autolink_SetLink($string, $titles) {
# In $titles choose best title regex match to $string, return HTML link.
  global $s, $root_rel;

  # Store each title's levenshtein distance to $string in $titles_ranked
  $titles_ranked = array();
  foreach ($titles as $title)
    $titles_ranked[$title] = levenshtein($title, $string);

  # Choose from $titles_ranked $title with lowest levenshtein distance.
  $title = '';
  $last_score = 9000;
  foreach ($titles_ranked as $title_ranked => $score)
    if ($score < $last_score)
    { $title      = $title_ranked;
      $last_score = $score; }

  # Build link.
  $s['i_title']  = $title;
  $s['i_string'] = $string;
  return ReplaceEscapedVars($s['Autolink_SetLink()']); }

#############
# Backlinks #
#############

function Autolink_Backlinks() {
  global $Autolink_dir, $s;

  # Don't do anything if there's no Autolink file for the page displayed
  $cur_page_file = $Autolink_dir.$s['page_title'];
  if (!is_file($cur_page_file))
    return; 

  # Build HTML of linked $links_in.
  $links_in = Autolink_GetFromFileLine($cur_page_file, 2, TRUE);
  foreach ($links_in as $link) {
    $s['i_title'] = $link; 
    $s['Autolink_Backlinks():links'] .= ReplaceEscapedVars(
                                     $s['Autolink_Backlinks():link']); }

  # No backlinks.
  if (!$links_in)
    $s['Autolink_Backlinks():links'] = $s['Autolink_NoBacklinks'];
 
  return $s['Autolink_Backlinks()']; }

####################
# Regex generation #
####################

function Autolink_BuildRegex($title) {
# Generate regular expression to match $title for autolinking in pages.
  $umlaut_table = array('äÄ' => array('ae', 'Ae'), 
                        'öÖ' => array('oe', 'Oe'),
                        'üÜ' => array('ue', 'Ue'),
                        'ß'  => array('ss'));
  foreach ($umlaut_table as $umlaut => $transl)
  { $umlaut_table_sub[$transl[0][0]] = $transl[0];
    $umlaut_table_sub[$transl[1][0]] = $transl[1]; }
  $encoding           = 'UTF-8';
  $minimal_root       = 4;
  $suffix_tolerance   = 3;
  $gaps_to_allow_easy = ' .,:\'';                       # Double symbols
  $gaps_to_allow_hard = array('/','\\','(',')','[',']');# transformed by
  $gaps_to_allow_long = array('&apos;');                # EscapeHTML().

  # "!"-divide on hyphens; at digit vs. char; char followed by uppercase
  $regex = preg_replace(        '/(-+)/',           '!',   $title);
  $regex = preg_replace(   '/([0-9])([A-Za-z])/', '$1!$2', $regex);
  $regex = preg_replace('/([A-Za-z])([0-9])/',    '$1!$2', $regex);
  $regex = preg_replace('/([A-Za-z])(?=[A-Z])/',  '$1!',   $regex);

  # Umlauts allowed in the tolerances at regex part ends (see next step)
  $legal_umlauts = '';
  foreach($umlaut_table as $umlaut => $translation)
    $legal_umlauts .= mb_substr($umlaut, 0, 1, $encoding);

  # Build toleration for char additions /changes, at regex part ends.
  $regex_parts      = explode('!', $regex);
  foreach ($regex_parts as &$part) {

    # In non-num. parts, see if changed ending chars can be tolerated.
    if (strpos('0123456789', $part[0]) === FALSE) {
     
      # $ln_flexible: number of chars in string left after $minimal_root
      $ln_part       = strlen($part);
      $ln_static     = min($minimal_root, $ln_part);
      $minimal_root -= $ln_static;
      $ln_flexible   = $ln_part - $ln_static;

      # $replace_tolerance: largest-possible mirror of $suffix_tolerance
      # fitting into $ln_flexible and not larger than 1/3 of $ln_part.
      $replace_tolerance = $suffix_tolerance;
      while ($replace_tolerance > 0) {
        if (    ($ln_flexible >= $replace_tolerance) 
            and ($ln_part >= 2 * $replace_tolerance)) {
          $part = substr($part, 0, -$replace_tolerance);
          break; }
        $replace_tolerance--; }

      # What if cut-off is inside an umlaut translation? Identify all
      # potential cut-off umlaut translations, replace with respective
      #  full versions.
      $last_char = substr($part, -1);
      foreach ($umlaut_table_sub as $char => $umlaut)
        if ($last_char == $char)
          $part = substr($part, 0, -1).$umlaut;

      # To a possibly reduced $part, add tolerance => $suffix_tolerance.
      $tolerance_sum = min($ln_part, ($replace_tolerance +
                                                    $suffix_tolerance));
      $part .= '([a-z'.$legal_umlauts.'\']|&apos;){0,'.$tolerance_sum.
                                                                  '}'; }

    # In a numerical $part, just add tolerance of $suffix_tolerance size
    else
      $part .= '([a-z'.$legal_umlauts.'\']|&apos;){0,'.$suffix_tolerance
                                                                 .'}'; }

  # $gaps_to_allow: glue for $regex_parts. Integrate $gaps_to_allow_easy
  #  as is, $...hard with escape chars and $...long with their own "or"
  # parantheses.
  $gaps_to_allow = $gaps_to_allow_easy;
  foreach ($gaps_to_allow_hard as $char)
    $gaps_to_allow .= '\\'.$char;
  $gaps_to_allow = '['.$gaps_to_allow.'\-]';
  if (!empty($gaps_to_allow_long))
    $gaps_to_allow =
       '(('.implode(')|(', $gaps_to_allow_long).')|'.$gaps_to_allow.')';
  $regex = implode($gaps_to_allow.'*', $regex_parts);

  # Make regexes umlaut-cognitive according to $umlaut_table.
  foreach ($umlaut_table as $umlaut => $transl) {

    # Slice uppercase and lowercase versions off $umlaut
    # *multibyte-compatibly*.
    $umlaut_lower = mb_substr($umlaut, 0, 1, $encoding);
    $umlaut_upper = mb_substr($umlaut, 1, 1, $encoding);

    # For multi-char umlaut translations, also allow first-char version.
    $transl_lower = $transl[0];
    $transl_upper = $transl[1];
    if (strlen($transl_lower) > 1) 
      $transl_lower = $transl_lower.'|'.$transl_lower[0];
    if (strlen($transl_upper) > 1) 
      $transl_upper = $transl_upper.'|'.$transl_upper[0];

    # Replace "ae" etc. with "(ä|ae)" etc. Check for uppercase versions.
    $regex = str_replace($transl[0], 
                       '('.$transl_lower.'|'.$umlaut_lower.')', $regex);
    if ($umlaut_upper != '')
      $regex = str_replace($transl[1],
                     '('.$transl_upper.'|'.$umlaut_upper.')', $regex); }

  return $regex; }

####################################
# DB updating / building / purging #
####################################

function Autolink_Update($title, $text, $diff) {
# Add to task list Autolink DB update. $text, $diff determine change.
  global $Autolink_dir, $nl;

  # Silently fail if Autolink DB directory does not exist.
  if (!is_dir($Autolink_dir)) return $t;

  # Some needed variables.
  $cur_page_file    = $Autolink_dir.$title;
  $all_other_titles = array_diff(GetAllPageTitles(), array($title));

  # Page creation wants new file, going through all pages for new links.
  if (!is_file($cur_page_file)) {
    $t .= 'Autolink_CreateFile("'.$title.'");'.$nl;
    foreach ($all_other_titles as $linkable) {
      $t .= 'Autolink_TryLinking("'.$title.'", "'.$linkable.'");'.$nl;
      $t .= 'Autolink_TryLinking("'.$linkable.'", "'.$title.'");'.$nl;}}

  else {
    $links_out  = Autolink_GetFromFileLine($cur_page_file, 1, TRUE);

    # Deletion severs links between files before $cur_page_file deletion
    if ($text == 'delete') {
      foreach ($links_out as $page)
        $t .= 'Autolink_ChLine("'.$page.'",2,"del","'.$title.'");'.$nl;
      $links_in = Autolink_GetFromFileLine($cur_page_file, 2, TRUE);
      foreach ($links_in as $page)
        $t .= 'Autolink_ChLine("'.$page.'",1,"del","'.$title.'");'.$nl;
      $t .= 'unlink("'.$cur_page_file.'")'.$nl; }

    # For mere page change, determine tasks comparing $diff:$links_out.
    else {
      # Divide $diff into $diff_del / $diff_add: lines deleted / added.
      $lines = explode($nl, $diff);
      $diff_del = array(); $diff_add = array();
      foreach ($lines as $line)
      if     ($line[0] == '<') $diff_del[] = substr($line, 1);
      elseif ($line[0] == '>') $diff_add[] = substr($line, 1);
  
      # Compare unlinked titles' regexes against $diff_add for new links
      $not_linked = array_diff($all_other_titles, $links_out);
      foreach (Autolink_TitlesInLines($not_linked, $diff_add) as $pn) {
        $t.='Autolink_ChLine("'.$title.'",1,"add","'.$pn.'");'.$nl;
        $t.='Autolink_ChLine("'.$pn.'",2,"add","'.$title.'");'.$nl; }
 
      # Threaten $links_out by matches in $diff_del. Remove threat if
      # regexes still matched in $diff_add or whole page $text. Else,
      # remove link_out.
      $links_rm = array();
      foreach (Autolink_TitlesInLines($links_out, $diff_del)
                                                           as $pagename)
        $links_rm[] = $pagename;
      foreach (Autolink_TitlesInLines($links_rm, $diff_add)
                                                           as $pagename)
        $links_rm = array_diff($links_rm, array($pagename)); 
      $lines_text = explode($nl, $text);
      foreach (Autolink_TitlesInLines($links_rm, $lines_text)
                                                           as $pagename)
        $links_rm = array_diff($links_rm, array($pagename));
      foreach ($links_rm as $pn) {
        $t .= 'Autolink_ChLine("'.$title.'",1,"del","'.$pn.'");'.$nl;
        $t .= 'Autolink_ChLine("'.$pn.'",2,"del","'.$title.'");'.$nl;}}}

  return $t; }

function Action_AutolinkAdmin() {
  global $Autolink_dir, $s;

  # Offer DB building/purging, dependant on existence of $Autolink_dir.
  if (!is_dir($Autolink_dir)) {
    $s['Action_AutolinkAdmin():question'] = $s['Autolink_Build'];
    $s['Action_AutolinkAdmin():do_what']  = 'build'; }
  else {
    $s['Action_AutolinkAdmin():question'] = $s['Autolink_Destroy'];
    $s['Action_AutolinkAdmin():do_what']  = 'destroy'; }

  $s['title']   = $s['Action_AutolinkAdmin():title'];
  $s['content'] = $s['Action_AutolinkAdmin():form']; 
  OutputHTML(); }

function PrepareWrite_Autolink_admin(&$redir) {
# Add tasks to build or delete an Autolink DB from scratch.
  global $Autolink_dir, $esc, $nl, $root_rel, $todo_urgent;
  $action = $_POST['do_what'];

  if ('build' == $action) {

    # Abort if $Autolink_dir found, else prepare task to create it.
    if (is_dir($Autolink_dir))
      ErrorFail('Autolink_NoBuildDB');
    $tasks = 'mkdir("'.$Autolink_dir.'");'.$nl;

    # Build page file creation, linking tasks.
    $titles = GetAllPageTitles();
    $string = '';
    foreach ($titles as $title)
    { $tasks .= 'Autolink_CreateFile("'.$title.'");'.$nl;
      $tasks .= 'Autolink_TryLinkingAll("'.$title.'");'.$nl; } }

  elseif ('destroy' == $action) {
  
    # Abort if $Autolink_dir found, else prepare task to create it.
    if (!is_dir($Autolink_dir))
      ErrorFail('Autolink_NoDestroyDB');
  
    # Add unlink(), rmdir() tasks for $Autolink_dir and its contents.
    $p_dir = opendir($Autolink_dir);
    while (FALSE !== ($fn = readdir($p_dir)))
      if (is_file($Autolink_dir.$fn))
        $tasks .= 'unlink("'.$Autolink_dir.$fn.'");'.$nl;
    closedir($p_dir); 
    $tasks .= 'rmdir("'.$Autolink_dir.'");'.$nl; }

  else
    ErrorFail('Autolink_InvalidDBAction'); 
    
  return $tasks; }

##########################################
# DB writing tasks to be called by todo. #
##########################################

function Autolink_CreateFile($title) {
# Start Autolink file of page $title, empty but for title regex.
  global $Autolink_dir, $nl;
  $path = $Autolink_dir.$title;
  if (!is_file($path)) {
    $content = Autolink_BuildRegex($title).$nl.$nl;
    $temp    = NewTemp($content);
    rename($temp, $path); } }

function Autolink_TryLinking($title, $linkable) {
# Try auto-linking both pages, write to their files.
  global $Autolink_dir, $nl, $pages_dir;
  $page_txt       = file_get_contents($pages_dir.$title);
  $regex_linkable = Autolink_RetrieveRegexForTitle($linkable);
  if (preg_match('/'.$regex_linkable.'/iu', $page_txt)) {
    Autolink_ChLine($title, 1, 'add', $linkable);
    Autolink_ChLine($linkable, 2, 'add', $title); } }

function Autolink_TryLinkingAll($title) {
  global $legal_title, $Autolink_dir; 

  $titles = array();
  $p_dir = opendir($Autolink_dir);
  while (FALSE !== ($fn = readdir($p_dir)))
    if (is_file($Autolink_dir.$fn)
        and preg_match('/^'.$legal_title.'$/', $fn))
      $titles[] = $fn;
  closedir($p_dir); 

  foreach ($titles as $linkable)
    if ($linkable != $title) {
      Autolink_TryLinking($title, $linkable); 
      Autolink_TryLinking($linkable, $title); } }

function Autolink_ChLine($title, $line_n, $action, $diff) {
# On $title's Autolink file, on $line_n, move $diff in/out by $action.
  global $Autolink_dir, $nl;
  $path = $Autolink_dir.$title;

  # Do $action with $diff on $title's file $line_n. "add": re-sort line.
  $lines          = explode($nl, file_get_contents($path));
  $strings        = explode(' ', $lines[$line_n]);
  if     ($action == 'add') {
    if (!in_array($diff, $strings))
      $strings[]  = $diff;
    usort($strings, 'Autolink_SortByLengthAlphabetCase'); }
  elseif ($action == 'del')
    $strings      = array_diff($strings, array($diff));  
  $new_line       = implode(' ', $strings);
  $lines[$line_n] = rtrim($new_line);
  $content        = implode($nl, $lines);

  # No check for prev attempts; redundant runs of the above are harmless
  $path_temp = NewTemp($content);
  rename($path_temp, $path); }

##########################
# Minor helper functions #
##########################

function Autolink_GetFromFileLine($path,$line_n,$return_as_array=FALSE){
# Return $line_n of file $path. $return_as_array string separated by ' '
# if set. From empty lines, explode() generates $x = array(''); return
# array() instead.
  global $nl;
  $x = explode($nl, file_get_contents($path));
  $x = $x[$line_n];
  if ($return_as_array)
    $x = explode(' ', $x);
    if ($x == array(''))
      return array();
  return $x; }

function Autolink_SortByLengthAlphabetCase($a, $b) {
# Try sort by stringlength, then follow sort() for upper- vs. lowercase.
  $strlen_a = strlen($a);
  $strlen_b = strlen($b);
  if     ($strlen_a < $strlen_b) return  1;
  elseif ($strlen_a > $strlen_b) return -1;

  $sort = array($a, $b);
  sort($sort);
  if ($sort[0] == $a) return -1;
  else                return  1; }

function Autolink_RetrieveRegexForTitle($title) {
# Return regex matching $title according to its Autolink file.
  global $Autolink_dir;
  $Autolink_file = $Autolink_dir.$title;
  $regex = Autolink_GetFromFileLine($Autolink_file, 0);
  return $regex; }

function Autolink_TitlesInLines($titles, $lines) {
# Return array of all $titles whose Autolink regex matches $lines.
 $titles_new = array();
  foreach ($titles as $title) {
    $regex = Autolink_RetrieveRegexForTitle($title);
    foreach ($lines as $line)
      if (preg_match('/'.$regex.'/iu', $line)) {
        $titles_new[] = $title;
        break; } }
  return $titles_new; }
