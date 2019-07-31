<?php
/**
 * english language file for cli plugin
 *
 * @author Schplurtz le Déboulonné <Schplurtz@laposte.net>
 */

// keys need to match the config setting name
$lang['prompt']   ='Regexp (delimited by / = , ; % or @) or string that describes the main prompt of the CLI. default is ‘/^.{0,30}?[$%&gt;#] /’. It matches the shortest text within 30 chars from the begining of line that ends in $, %, &gt;, # followed by a space. It works well for a majority of cli, including shells.<br />If your prompt and your secondary prompt both end in ‘&gt; ’ you will have to make this regexp or the regexp for the secondary prompt more specific as this regexp will match both prompts';
$lang['continue'] ='Regexp (delimited by / = , ; % or @) or string that describe the CLI secondary prompt. Default value is ‘/^.{0,30}?&gt; /’. It matches the shortest text not longer than 30 chars that ends in ‘&gt; ’.';
$lang['comment']  ='Comment regexp or string. Default is ‘/(^#)| #/’. It matches a # at the begining of line or a space followed by a sharp sign.';
$lang['namedprompt']='named prompt list, one per line, using this format : "name:regexp or string"<br />Name may then be used as shortcut in wiki pages this way <br /><tt>&lt;cli t=name&gt;</tt><br />It is quite shorter than <br /><tt>&lt;cli prompt="blabla" continue="blibli" comment="zap"&gt;</tt>';
$lang['namedcontinue']='named secondary prompt list, one per line, using this format : "name:regexp or string"';
$lang['namedcomment']='named comment list, one per line, using this format : "name:regexp or string"';
$lang['odtbackground']  = 'When exporting pages to ODT or PDF format with the <a href="https://www.dokuwiki.org/plugin:odt">odt</a> plugin, background color of the <tt>&lt;cli></tt> blocks. The default value <tt>#f7f9fa</tt> is the same as the one used by the <tt>&lt;code></tt> blocs.';
$lang['odtborderwidth'] = 'When exporting pages to ODT or PDF format with the <a href="https://www.dokuwiki.org/plugin:odt">odt</a> plugin, width in a CSS <a href="https://developer.mozilla.org/en-US/docs/Web/CSS/length">unit length</a>, usually point «pt», of the border of the <tt>&lt;cli></tt> blocs. Default value : 0.06pt';
$lang['odtbordercolor'] = 'When exporting pages to ODT or PDF format with the <a href="https://www.dokuwiki.org/plugin:odt">odt</a> plugin, bordercolor of the <tt>&lt;cli></tt> blocs. Default is #8cacbb';


//Setup VIM: ex: et ts=4 :
