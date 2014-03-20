<?php
# PlomWiki plugin: MarkupImage. StandardMarkup for image display.
# 
# Copyright 2014 Christian Heller / <http://www.plomlompom.de/>
# License: AGPLv3 or any later version. See file LICENSE for details.

function MarkupImage($text) {
# Example: "[:img <http://example.org/example.jpg> Alternative text :]"
# Alt text mustn't contain newlines. Tag must start and end its line.
# Relies on MarkupEscape(), must be added before this and MarkupLinks().
  global $esc, $nl;
  $lines = explode($nl, $text);
  foreach ($lines as $line) {
    $line = preg_replace('/^\[:img &lt;(.*?)\&gt; (.*?) :\]$/',
                         $esc.'[=<img src="$1" alt="$2">=]', $line);
    $new_text .= $nl.$line; }
  $new_text = substr($new_text, 1);
  return $new_text; }
