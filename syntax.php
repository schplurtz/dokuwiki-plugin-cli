<?php
/**
 * Command Line Interface (CLI) Plugin
 *     Typeset transcripts of interactive sessions with mimumum effort.
 * Syntax:
 *     <cli prompt="$ " continue="> " comment="#">
 *   or
 *     <cli prompt="/^.*?[%$#>] /" continue="/^(?:|[^=]|..*?)> /" comment="#">
 *     user@host:~/somedir $ ls \
 *     > # List directory
 *     file1 file2
 *     </cli>
 *   prompt --- [optional] prompt character or regexp used. '/^.{0,30}?[>%$#] /' is default - note the space.
 *   continue --- [optional] regex of shell continuation '/^.{0,30}?> /' is the default - note the space
 *   comment --- [optional] comment character used. '/(^#)| #/' is default - note no space after #.
 *   The defaults above match many cli defaults. Bourne shell ${PS1} and ${PS2} prompts and comment among others.
 *
 * Acknowledgements:
 *  Borrows heavily from the boxes plugin!
 *  Support for continuation added by Andy Webber
 *  Improved parsing added by Stephane Chazelas
 *  proper <cli> nesting. regexp for prompt and comments, shortcuts, config options, French translation, better continuation by Schplurtz le Déboulonné.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Chris P. Jobling <C.P.Jobling@Swansea.ac.uk>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_cli extends DokuWiki_Syntax_Plugin {

    # prompt, continue and comment stack
    var $stack=array(array('/^.{0,30}?[$%>#] /', '/^.{0,30}?> /', '/(^#)| #/'));
    var $namedpcc=array();
    var $initp=false;


    # init not done in constructor on purpose
    function init() {
        $this->initp=true;
        if(''!=$this->getConf('prompt')) $this->stack[0][0]=$this->_toregexp($this->getConf('prompt'));
        if(''!=$this->getConf('continue')) $this->stack[0][1]=$this->_toregexp($this->getConf('continue'));
        if(''!=$this->getConf('comment')) $this->stack[0][2]=$this->_toregexp($this->getConf('comment'),0);
        $this->loadnamedparam($this->getConf('namedprompt'), 'prompt'); 
        $this->loadnamedparam($this->getConf('namedcontinue'), 'continue'); 
        $this->loadnamedparam($this->getConf('namedcomment'), 'comment'); 
    }
    /**
     * return some info
     */
    function getInfo(){
        return confToHash(dirname(__FILE__).'/plugin.info.txt');
    }
    
    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'protected';
    }

    /**
     * What kind of syntax do we allow (optional)
     */
//    function getAllowedTypes() {
//        return array();
//    }

    // override default accepts() method to allow nesting
    // - ie, to get the plugin accepts its own entry syntax
    function accepts($mode) {
        if ($mode == substr(get_class($this), 7)) return true;
        return parent::accepts($mode);
    }

    /**
     * What about paragraphs? (optional)
     */
    function getPType(){
        return 'block';
    }

    /**
     * Where to sort in?
     */
    function getSort(){
        return 601;
    }


    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
         $this->Lexer->addEntryPattern('<cli(?:[)]?' .
             '"(?:\\\\.|[^\\\\"])*"' .     /* double-quoted string */
             '|\'(?:\\\\.|[^\'\\\\])*\'' . /* single-quoted string */
             '|\\\\.' .                    /* escaped character */
             '|[^\'"\\\\>]|[(?:])*>\r?\n?(?=.*?</cli>)',$mode,'plugin_cli');
         /*
          * The [)]? and |[(?:] is to work around a bug in lexer.php
          * wrt nested (...)
         */
    }

    function postConnect() {
       $this->Lexer->addExitPattern('\r?\n?</cli>','plugin_cli');
    }


    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        switch ($state) {
          case DOKU_LEXER_ENTER :
            $args = substr($match, 4, -1);
            return array($state, $args);
          case DOKU_LEXER_MATCHED :
            break;
          case DOKU_LEXER_UNMATCHED :
            return array($state, $match);
          case DOKU_LEXER_EXIT :
            return array($state, '');
          case DOKU_LEXER_SPECIAL :
            break;
        }
        return array();
    }

    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
	if(!$this->initp) $this->init();
        if($mode == 'xhtml'){
             list($state, $match) = $data;
             switch ($state) {
             case DOKU_LEXER_ENTER :
                 $args = $match;
                 $this->_get_and_push_prompts($args);
                 $renderer->doc .= '<pre class="cli">';
                 break;
             case DOKU_LEXER_UNMATCHED :
                 $this->_render_conversation($match, $renderer);
                 break;
             case DOKU_LEXER_EXIT :
                 array_pop($this->stack);

                 $renderer->doc .= "</pre>";

                 break;
             }
             return true;
        }
        return false;
    }

    function loadnamedparam($s, $type){
        foreach(preg_split('/\n\r|\n|\r/',$s) as $line){
            if(''==$line)
                continue;
            list($nom,$val)=explode(':', $line, 2);
            $this->namedpcc[$nom][$type]=($type == 'comment') ? $this->_toregexp($val,0) : $this->_toregexp($val);
        }
    }
     function _extract($args, $param) {
         /*
          * extracts value from $args for $param
          * xxx = "foo\"bar"  -> foo"bar
          * xxx = a\ b        -> a b
          * xxx = 'a\' b'     -> a' b
          *
          * returns null if value is empty.
          */
         if (preg_match("/$param" . '\s*=\s*(' .
             '"(?:\\\\.|[^\\\\"])*"' .     /* double-quoted string */
             '|\'(?:\\\\.|[^\'\\\\])*\'' . /* single-quoted string */
             '|(?:\\\\.|[^\\\\\s])*' .     /* escaped characters */
             ')/', $args, $matches)) {
             switch (substr($matches[1], 0, 1)) {
             case "'":
                 $result = substr($matches[1], 1, -1);
                 $result = preg_replace('/\\\\([\'\\\\])/', '$1', $result);
                 break;
             case '"':
                 $result = substr($matches[1], 1, -1);
                 $result = preg_replace('/\\\\(["\\\\])/', '$1', $result);
                 break;
             default:
                 $result = preg_replace('/\\\\(.)/', '$1', $matches[1]);
             }
             if ($result != "")
                 return $result;
         }
     }

    function _get_and_push_prompts($args) {
        // process args to CLI tag: sets $comment_str and $prompt_str and $prompt_cont
        $name=rtrim($this->_extract($args, 't'),'>');
        $this->stack[]=array(
            ($s = $this->_extract($args, 'prompt')) ? $this->_toregexp($s) : ($this->namedpcc[$name]['prompt'] ? $this->namedpcc[$name]['prompt'] : $this->stack[0][0]),
            ($s = $this->_extract($args, 'continue')) ? $this->_toregexp($s) : ($this->namedpcc[$name]['continue'] ? $this->namedpcc[$name]['continue'] : $this->stack[0][1]),
            ($s = $this->_extract($args, 'comment')) ? $this->_toregexp($s,0) : ($this->namedpcc[$name]['comment'] ? $this->namedpcc[$name]['comment'] : $this->stack[0][2]),
        );
    }

    function _toregexp( $s, $from_start_of_line=1 ) {
        if(preg_match('/^([\/=,;%@]).+(\1)$/', $s)) {
            return $s;
        }
        $r= $from_start_of_line? '/^.*?' : '/';
        foreach( str_split( $s ) as $c )
            $r .= ('\\' == $c || $c == '/') ? "[\\$c]" :  "[$c]";
        $r .= '/';
        return $r;
    }
    function _render_conversation($match, &$renderer) {
        list( $prompt_str, $prompt_cont, $comment_str )=end($this->stack);
        $prompt_continues = false;
        $lines = preg_split('/\n\r|\n|\r/',$match);
        if ( trim($lines[0]) == "" ) unset( $lines[0] );
        if ( trim($lines[count($lines)]) == "" ) unset( $lines[count($lines)] );
        $prompt_continue=false;
        foreach($lines as $line) {
            if($prompt_continue) {
                if (preg_match($prompt_cont, $line, $promptc)) {
                    // format prompt
                    $renderer->doc .= '<span class="cli_prompt">' . $renderer->_xmlEntities($promptc[0]) . "</span>";
                    // Split line into command + optional comment (only end-of-line comments supported)
                    $command =  preg_split($prompt_cont, $line, 2);
                    $commands = preg_split($comment_str, $command[1], 2);
                    // Render command
                    $renderer->doc .= '<span class="cli_command">' . $renderer->_xmlEntities($commands[0]) . "</span>";
                    // Render comment if there is one
                    if ($commands[1]) {
                        preg_match( $comment_str, $command[1], $comment);
                        $renderer->doc .= '<span class="cli_comment">' .
                        $renderer->_xmlEntities($comment[0] . $commands[1]) . "</span>";
                    }
                    $renderer->doc .= DOKU_LF;
                    continue;
                }
            }
            if (preg_match($prompt_str, $line, $matches)) {
                $prompt_continue=true;
                $index=strlen($matches[0]);
                // format prompt
                $prompt = substr($line, 0, $index);
                $renderer->doc .= '<span class="cli_prompt">' . $renderer->_xmlEntities($prompt) . "</span>";
                // Split line into command + optional comment (only end-of-line comments supported)
                $commands = preg_split($comment_str, substr($line, $index),2);
                // Render command
                $renderer->doc .= '<span class="cli_command">' . $renderer->_xmlEntities($commands[0]) . "</span>";
                // Render comment if there is one
                if ($commands[1]) {
                    preg_match( $comment_str, substr($line, $index), $comment);
                    $renderer->doc .= '<span class="cli_comment">' .
                    $renderer->_xmlEntities($comment[0] . $commands[1]) . "</span>";
                }
                $renderer->doc .= DOKU_LF;
                continue;
            }
            // render as output
            $renderer->doc .= '<span class="cli_output">' . $renderer->_xmlEntities($line) . "</span>" . DOKU_LF;
            $prompt_continue=false;
        }
    }
}
//Setup VIM: ex: et ts=4 enc=utf-8 sw=4 :
