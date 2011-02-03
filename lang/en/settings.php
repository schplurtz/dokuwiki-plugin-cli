<?php

$lang['prompt']   ='Regexp (delimited by / = , ; % or @) or string that describe the main prompt of the CLI. default is ‘/^.{0,30}?[$%>#] /’. It matches the shortest text within 30 chars from the begining of line that ends in $, %, >, # followed by a space. It is works well for a majority of cli.<br />If your prompt and your secondary prompt both ends in ‘> ’ you will have to make this regexp or the regexp dor the secondary prompt more specific as this regexp will match both prompts';
$lang['continue'] ='Regexp (delimited by / = , ; % or @) or string that describe the CLI secondary prompt. Default value is ‘/^.{0,30}?> /’. It matches the shortest text not longer than 30 chars that ends in ‘> ’.';
$lang['comment']  ='Comment regexp or string. Default is ‘/(^#)| #/’. It matches a # at the begining of line or a space folled by a space.';
$lang['namedprompt']='named prompt list, one on each line, using this format : "name:regexp or string"<br />Name may then be used as shortcuts in wiki pages this way <br /><tt>&lt;cli t=name&gt;</tt><br />It is quite shorter than <br /><tt>&lt;cli prompt="blabla" continue="blibli" comment="zap"&gt;</tt>';
$lang['namedcontinue']='named secondary prompt list, one on each line, using this format : "name:regexp or string"';
$lang['namedcomment']='named comment list, one on each line, using this format : "name:regexp or string"';

