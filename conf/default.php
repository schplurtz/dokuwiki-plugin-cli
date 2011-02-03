<?php

$conf['prompt']   = '/^.{0,30}?[$%>#] /';
$conf['continue'] = '/^.{0,30}?> /';
$conf['comment']  = '/(^#)| #/';
$conf['namedprompt']	= "irb:/^(irb.*?|>)> /";
$conf['namedcontinue']	= "irb:..\nR:/^\+ /\npython:...";
$conf['namedcomment']	= "";

