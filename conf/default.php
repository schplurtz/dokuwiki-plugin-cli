<?php
/**
 * Default settings for the cli plugin
 *
 * @author Schplurtz le Déboulonné <Schplurtz@laposte.net>
 */

$conf['prompt'] = '/^.{0,30}[$%>#](?:$|\\s)/';
$conf['continue'] = '/^.{0,30}>(?:$|\\s)/';
$conf['comment'] = '/(:?^#)|\\s#/';
$conf['namedprompt'] = '
irb:/(?x) ^ ( > | irb.*?:\\d+:\\d+ | (?: ruby-?)? \\d.*?:\\d+\\s )>\\s /
nospace:/^.{0,30}[$%>#]/
dos:/^[A-Z]:.{0,28}>/
';
$conf['namedcontinue'] = '
irb:@(?x) ^ ( \\?> | irb.*?:\\d+:\\d+[]\'"/`*>] | (?: ruby-?)? \\d.*?:\\d+[]\'"/`?]> )\\s @
R:/^\+ /
python:...
dos:undef continue
nospace:undef continue
';
$conf['namedcomment'] = '
irb:/#(?!{)/
dos:/^\s*rem(\s+|$)/
';
