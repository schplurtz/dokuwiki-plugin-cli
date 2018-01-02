<?php
/**
 * Default settings for the cli plugin
 *
 * @author Schplurtz le Déboulonné <Schplurtz@laposte.net>
 */

$conf['prompt'] = '/^.{0,30}?[$%>#](?:$|\\s)/';
$conf['continue'] = '/^.{0,30}?>(?:$|\\s)/';
$conf['comment'] = '/(:?^#)|\\s#/';
$conf['namedprompt'] = 'irb:/^(irb.*?|>)> /
nospace:/^.{0,30}?[$%>#]/
dos:/^[A-Z]:.{0,28}?>/';
$conf['namedcontinue'] = 'irb:..
R:/^\\+ /
python:...
nospace:/^.{0,30}?[$%>#]/';
$conf['namedcomment'] = 'dos:/^\\s*rem(\\s+|$)/';
