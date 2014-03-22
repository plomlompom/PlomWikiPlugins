<?php
# PlomWiki plugin: PlomDesign. Design/layout of <www.plomlompom.de>.
#
# Copyright 2014 Christian Heller / <http://www.plomlompom.de/>
# License: AGPLv3 or any later version. See file LICENSE for details.

$s = ReadStringsFile($plugin_strings_dir.'DesignBlogPlomRogue', $s);

$hook_before_action .= '
if (\'Start\' == $title and $action == \'Action_page_view\')
  $action = \'Action_Blog\';
if ($action == \'Action_Blog\') {
  $s["DesignBlogPlomRogue_TitleTagContent"] =
                     $s["DesignBlogPlomRogue_TitleTagContent_Overview"];
  $s["DesignBlogPlomRogue_ContentDisplay"] =
                $s["DesignBlogPlomRogue_ContentDisplay_BlogOverview"]; }
if ($action == \'Action_page_view\' and Blog_PageIsArticle($title)) {
  $s["DesignBlogPlomRogue_AboveCommentsInput"] =
                        $s["DesignBlogPlomRogue_Comments_CloseArticle"];
  $s["DesignBlogPlomRogue_ContentDisplay"] =
                 $s["DesignBlogPlomRogue_ContentDisplay_BlogArticle"]; }
';
