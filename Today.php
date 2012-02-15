<?php
# PlomWiki plugin: Today markup
# 
# Provides [:today:] markup, displays current date in format "Y-m-d".

function MarkupToday($text) {
# Translates [:today:] into the current date, formatted as Y-m-d.
  return str_replace('[:today:]', date('Y-m-d'), $text); }
