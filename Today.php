<?php
# PlomWiki plugin: Today. Markup [:today:] displays current Y-m-d date.
# 
# Copyright 2010-2012 Christian Heller / <http://www.plomlompom.de/>
# License: AGPLv3 or any later version. See file LICENSE for details.

function MarkupToday($text) {
# Translates [:today:] into the current date, formatted as Y-m-d.
  return str_replace('[:today:]', date('Y-m-d'), $text); }
