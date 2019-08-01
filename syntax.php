<?php
/**
 * DokuWiki Plugin cli (Syntax Component)
 *
 * @license      GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author       Schplurtz le Déboulonné <schplurtz@laposte.net>
 * @author       Chris P. Jobling <C.P.Jobling@Swansea.ac.uk>
 * @author       Stephane Chazelas <stephane.chazelas@emerson.com>
 * @author       Andy Webber <dokuwiki@andywebber.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class syntax_plugin_cli extends DokuWiki_Syntax_Plugin {

    const PROMPT=0;
    const CONT=1;
    const COMMENT=2;
    const TYPE=3;
    const STYLE=4;
    // prompt, continue and comment stack
    protected $stack;
    protected $namedpcc=array();
    protected $init=false;
    protected $genhtml='';

    function __construct() {
        // Delay init until we actually need to parse some <cli>
        return;
    }

    /**
     * @return string Syntax mode type
     */
    public function getType() {
        return 'protected';
    }
    /**
     * What about paragraph ?
     *
     * Because we want to nest without having an open paragraph when an inner
     * cli is closed, we lie. We will close and open paragraph ourselves.
     *
     * @return string Paragraph type
     */
    public function getPType() {
        return 'normal';
    }
    /**
     * @return int Sort order - Low numbers go before high numbers
     */
    public function getSort() {
        return 601;
    }

    /**
     * delaied initialization.
     *
     * @return null
     */
    protected function _init() {
        if( $this->init ) return;
        // DokuWiki always loads and instanciates the plugin.
        // We don't want to load all this when the class is
        // loaded. Only when the syntax is really met and there
        // is need to parse should we do all this. It's not
        // even needed to render() a conversation.

        // hardcoded defaults
        $this->stack=array(array('/^.{0,30}[$%>#](?:$|\\s)/', '/^.{0,30}>(?:$|\\s)/', '/(?:^#)|\\s#/', '', ''));
        // override defaults with user config if exists.
        if(''!=($s=$this->getConf('prompt')))
            $this->stack[0][self::PROMPT]=$this->_toregexp($s);
        if(''!=($s=$this->getConf('continue')))
            $this->stack[0][self::CONT]=$this->_toregexp($s);
        if(''!=($s=$this->getConf('comment')))
            $this->stack[0][self::COMMENT]=$this->_toregexp($s, 1);
        $this->_parsenamedparam($this->getConf('namedprompt'), self::PROMPT);
        $this->_parsenamedparam($this->getConf('namedcontinue'), self::CONT);
        $this->_parsenamedparam($this->getConf('namedcomment'), self::COMMENT);
        $this->init = true;
    }
    /**
     * override default accepts() method to allow nesting
     *
     * ie, to get the plugin accepts its own entry syntax
     */
    function accepts($mode) {
        if ($mode == substr(get_class($this), 7)) return true;
        return parent::accepts($mode);
    }
    /**
     * Connect lookup pattern to lexer.
     *
     * @author       Stephane Chazelas <stephane.chazelas@emerson.com>
     * @author       Schplurtz le Déboulonné <schplurtz@laposte.net>
     * @param string $mode Parser mode
     */
    public function connectTo($mode) {
        // by the way, '<cli.*? >\r?\n?(?=.*?</cli>)' is the worst idea ever.
        $this->Lexer->addEntryPattern('<cli(?:[)]?' .
            '"(?:\\\\.|[^\\\\"])*"' .     /* double-quoted string */
            '|\'(?:\\\\.|[^\'\\\\])*\'' . /* single-quoted string */
            '|\\\\.' .                    /* escaped character */
            '|[^\'"\\\\>]|[(?:])*>\r?\n?'.
            '(?=.*?</cli>)'
            ,$mode,'plugin_cli');

            /*
             * The [)]? and |[(?:] is to work around a bug in lexer.php
             * wrt nested (...)
             */
    }

    /**
     * Connect exit pattern to lexer.
     *
     * @author       Stephane Chazelas <stephane.chazelas@emerson.com>
     */
    function postConnect() {
        $this->Lexer->addExitPattern('\r?\n?</cli>','plugin_cli');
    }

    /**
     * Handle matches of the cli syntax
     *
     * @author Schplurtz le Déboulonné <Schplurtz@laposte.net>
     * @param string          $match   The match of the syntax
     * @param int             $state   The state of the handler
     * @param int             $pos     The position in the document
     * @param Doku_Handler    $handler The handler
     * @return mixed[] array of "lines". a "line" is a String or String[3]
     */
    public function handle($match, $state, $pos, Doku_Handler $handler){
        switch ($state) {
        case DOKU_LEXER_ENTER :
            $this->_init();
            $level=count($this->stack) - 1;
            $args = substr(rtrim($match), 4, -1); // strip '<cli' and '>'
            $params=$this->_parseparams($args);
            $type=$params['type'];
            $style=$params['style'];
            $this->current=array();
            // nested cli that only define style inherit prompts
            if( $level && !empty($style) && ! $type && ! $params['prompt']  && ! $params['continue']  && ! $params['comment'] ) {
                $last=end($this->stack);
                $this->current[self::PROMPT]=$last[self::PROMPT];
                $this->current[self::CONT]=$last[self::CONT];
                $this->current[self::COMMENT]=$last[self::COMMENT];
                $this->current[self::TYPE]=$last[self::TYPE];
            }
            else {
                $this->current[self::PROMPT]=($params['prompt']) ?
                    $this->_toregexp($params['prompt'])
                    : (($type && ($t=$this->namedpcc[$type][self::PROMPT])) ?
                        $t
                        : $this->stack[0][self::PROMPT]
                    );
                $this->current[self::CONT]=($params['continue']) ?
                    $this->_toregexp($params['continue'])
                    : (($type && ($t=$this->namedpcc[$type][self::CONT])) ?
                        $t
                        : $this->stack[0][self::CONT]
                    );
                $this->current[self::COMMENT]=($params['comment']) ?
                    $this->_toregexp($params['comment'],1)
                    : (($type && ($t=$this->namedpcc[$type][self::COMMENT])) ?
                        $t
                        : $this->stack[0][self::COMMENT]
                    );
                $this->current[self::TYPE]=$type;
            }
            $this->current[self::STYLE]=$style;
            $this->stack[]=$this->current;
            // return nesting level and type and style
            return array($state, count($this->stack) - 2, $type, $style);
        case DOKU_LEXER_UNMATCHED :
            // return parsed conversation and type and style
            $top=end($this->stack);
            return array( $state, $this->_parse_conversation($match), $top[self::TYPE], $top[self::STYLE] );
        case DOKU_LEXER_EXIT :
            $top=array_pop($this->stack);
            $this->current=end($this->stack);
            // return same nested level as DOKU_LEXER_ENTER and type and style
            return array($state, count($this->stack) -1, $top[self::TYPE], $top[self::STYLE] );
        }
        return array(); //not reached
    }
    /**
     * analyze the conversation.
     *
     * The conversation is split in lines and analyzed line by
     * line. If no prompt can be recognised on a line, then that
     * line is obviously a computer output and it is kept as it
     * is. Otherwise, the line is further split into (prompt,
     * input, comment) triplet. Input and comment may be empty.
     *
     * @author Schplurtz le Déboulonné <Schplurtz@laposte.net>
     * @author Andy Webber <dokuwiki@andywebber.com>
     * @param  $txt     String   potentially multiline string
     * @return mixed[]           array of String or Array
     */
    protected function _parse_conversation($txt) {
        $res=array();
        $main_prompt=$this->current[self::PROMPT];
        $cont_prompt=$this->current[self::CONT];
        $lines = preg_split('/\n\r|\n|\r/',$txt);
        // skip first and last line if they are empty
        if ( trim($lines[0]) == '' ) unset( $lines[0] );
        if ( trim(end($lines)) == '' ) array_pop($lines);
        // continuation lines can only appear after a main-prompt line or continuation-line
        // but NOT as the first prompt. IE not after a line where there was no prompt.
        $prompt_continue=false;
        $parsed_lines=array();
        foreach($lines as $line) {
            if ($prompt_continue && preg_match($cont_prompt, $line, $matches)) {
                $parsed_lines[]=$this->_parseline( $line, $matches[0] );
                continue;
            }
            $prompt_continue=false;
            if (preg_match($main_prompt, $line, $matches)) {
                $prompt_continue=true;
                $parsed_lines[]=$this->_parseline( $line, $matches[0] );
                continue;
            }
            $parsed_lines[]=$line;
        }
        return $parsed_lines;
    }
    /**
     * split line in (prompt, command, comment) triplet.
     *
     * @author Schplurtz le Déboulonné <Schplurtz@laposte.net>
     * @param  $line    String   the original line
     * @param  $prompt  String   The current recognised prompt
     * @return String[]          the 3 components of the line : prompt, command, comment
     */
    protected function _parseline( $line, $prompt ) {
        $comment='';
        $index=strlen($prompt);
        $comcom = substr( $line, $index );
        $ar=preg_split($this->current[self::COMMENT], $comcom, 2, PREG_SPLIT_DELIM_CAPTURE);
        if( isset($ar[1]) ) {
            $comment=$ar[1].end($ar);
        }
        $ret=array( $prompt, $ar[0], $comment );
        return $ret;
    }

    /**
     * Render output. step by step generate html.
     * When generation is complete, check mode : if mode is xhtml, then
     * adds generate text to document. If mode is odt, then call odt renderer
     * that will convert html to odt.
     *
     * @param string         $mode      Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer  $renderer  The renderer
     * @param array          $data      The data from the handler() function
     * @return bool If rendering was successful.
     */
    public function render($mode, Doku_Renderer $renderer, $data) {
        if($mode !== 'xhtml' && $mode !== 'odt' && $mode !== 'odt_pdf') {
            return false;
        }
        list($state, $thing, $type, $style) = $data;
        switch ($state) {
        case DOKU_LEXER_ENTER :
            // $thing is nesting level here.
            // for outer <cli>, initialize string.
            // for nested <cli>, add a div.
            if( 0 == $thing ) {
                $this->genhtml = '';
            }
            else {
                $this->genhtml .= "<div class='$type $style'>";
                if( $mode != 'xhtml' ) // odt needs an additional CR. bug ?
                     $this->genhtml .= DOKU_LF;
            }
        break;
        case DOKU_LEXER_UNMATCHED :
            // Here $thing is an array of parsed lines as returned by _parseline
            $not_first_line=false;
            foreach( $thing as $line ) {
                if($not_first_line)
                    $this->genhtml .= DOKU_LF;
                else
                    $not_first_line=true;
                if(is_array($line)) {
                    $this->genhtml .= '<span class="cli_prompt">' . hsc($line[0]) . "</span>";
                    if( '' != $line[1] )
                        $this->genhtml .= '<span class="cli_command">' . hsc($line[1]) . "</span>";
                    if( '' != $line[2] )
                        $this->genhtml .= '<span class="cli_comment">' . hsc($line[2]) . "</span>";
                } else {
                    $this->genhtml .= '<span class="cli_output">' . hsc($line) . "</span>";
                }
            }
        break;
        case DOKU_LEXER_EXIT :
            // $thing is nesting level here.
            // only close <pre> if we're closing the outermost <cli>
            if( 0 === $thing ) {
                if( $mode == 'xhtml' ) {
                    $renderer->doc .= "</p><pre class='cli $type $style'>";
                    $renderer->doc .= $this->genhtml;
                    $renderer->doc .= '</pre><p>';
                }
                else {
                    if(!$renderer->styleExists('Command Line Interface')) {
                        $style=array(//FIXME: list of style porperties is in lib/plugins/odt/ODT/styles/ODTParagraphStyle.php
                                     'style-name' => 'Command Line Interface',
                                     'style-display-name' => 'Command Line Interface',
                                     'background-color' => $this->getConf('odtbackground'),
                                     'border' => $this->getConf('odtborderwidth').' solid '.
                                                 $this->getConf('odtbordercolor'),
                                    );
                        $renderer->createParagraphStyle( $style );
                    }
                    $options=array();
                    // see https://github.com/LarsGit223/dokuwiki-plugin-odt/commit/19f42d58f1d97758a2ccbac38aae7253826eb59a
                    $options ['escape_content'] = 'false';
                    $options ['space'] = 'preserve';
                    $options ['media_selector'] = 'screen';
                    $options ['p_style'] = 'Command Line Interface';
                    $options ['element'] = 'pre';
                    $renderer->generateODTfromHTMLCode($this->genhtml, $options);
                }
            }
            else // closing inner <cli>
                $this->genhtml .= '</div>';
                if( $mode != 'xhtml' ) // odt needs an additional CR. bug ?
                     $this->genhtml .= DOKU_LF;
        break;
        }
    }

    /**
     * parse named prompts or comments from config
     *
     * @author Schplurtz le Déboulonné <Schplurtz@laposte.net>
     * @param  $s             String        The configuration value
     * @param  $kind          Int           One of self::PROMPT, CONT, COMMENT
     * @return void
     */
    protected function _parsenamedparam($s, $kind) {
        foreach(preg_split('/\n\r|\n|\r/',$s) as $line){
            if(''==$line)
                continue;
            list($nom,$val)=explode(':', $line, 2);
            $this->namedpcc[$nom][$kind]=$this->_toregexp($val, $kind == self::COMMENT);
        }
    }

    /**
     * transform a string or regexp into a regexp.
     *
     * The string is to match either a prompt
     * or a comment, and is thus anchored accordingly.
     *
     * @author Schplurtz le Déboulonné <Schplurtz@laposte.net>
     * @param  $s             String        The string to transform
     * @param  $is_comment_re Boolean       true if the re is going to match a comment.
     * @return String                       The regexp.
     */
    function _toregexp( $s, $is_comment_re=false ) {
        if(preg_match('/^([\/=,;%@#]).+(\1)$/', $s)) {
            if( $is_comment_re )
                $s = $s[0] . '(' . substr( $s, 1, -1 ) . ')' . $s[0];
            return $s;
        }
        $r= $is_comment_re? '/(' : '/^.*?';
        foreach( str_split( $s ) as $c )
            $r .= ('\\' == $c || $c == '/') ? "[\\$c]" :  "[$c]";
        $r .= $is_comment_re? ')/' : '/';
        return $r;
    }

    /**
     * tokenize a string.
     *
     * recognize bare word, =, \-escaped char, and single or double quoted strings.
     * ie «a\ b="foo\"bar"» produces "a b", '=', 'foo"bar'.
     * This function implements the following DFA. See dot(1) if you
     * need to visualize it.
     * digraph {
     *   node [shape=circle];
     *   0 -> 0 [label="\\s"]
     *   0 -> 1 [label="\""]
     *   0 -> 3 [label="'"]
     *   0 -> 6 [label="\\ [+]"]
     *   0 -> 7 [label="= [+]"]
     *   0 -> 5 [label=". [+]"]
     * 
     *   1 -> 2 [label="\\ [+]"]
     *   1 -> 0 [label="\" [A]"]
     *   1 -> 1 [label=". [+]"]
     *   2 -> 1 [label="[\"\\] [-]"]
     *   2 -> 8 [label=". [+]"]
     *   8 -> 2 [label="\\ [+]"]
     *   8 -> 0 [label="\" [A]"]
     *   8 -> 1 [label=". [+]"]
     * 
     *   3 -> 4 [label="\\ [+]"]
     *   3 -> 0 [label="' [A]"]
     *   3 -> 3 [label=". [+]"]
     *   4 -> 3 [label="['\\] [-]"]
     *   4 -> 9 [label=". [+]"]
     *   9 -> 4 [label="\\ [+]"]
     *   9 -> 0 [label="' [A]"]
     *   9 -> 3 [label=". [+]"]
     * 
     *   5 -> 6 [label="\\"]
     *   5 -> 0 [label="\\s [A]"]
     *   5 -> 7 [label="= [A+]"]
     *   5 -> 1 [label="\" [A]"]
     *   5 -> 3 [label="' [A]"]
     *   5 -> 5 [label=". [+]"]
     * 
     *   6 -> 5 [label="[\"' =\\] [-]"]
     *   6 -> 5 [label=". [+]"]
     * 
     *   7 -> 0 [label="\\s [A]"]
     *   7 -> 1 [label="\" [A]"]
     *   7 -> 3 [label="' [A]"]
     *   7 -> 6 [label="\\ [A+]"]
     *   7 -> 5 [label=". [A+]"]
     *   e [shape=box,label="arc label : current char [actions]\n+: add current char to token\n-: replace last char in token with current char\nA: Accept cur token. New token\nInitial state: 0\nValid end states : 0, 5, 7"]
     * }
     *
     * @author Schplurtz le Déboulonné
     * @param $str String The string to tokenize
     * @return String[] An array of tokens
     */
    protected function _tokenize( $str ) {
        $trs=array(
            0 => array( ' ' => 0, "\t" => 0, '"' => 1, "'" => 3, '\\' => 6, '=' => 7, 'def' => 5 ),
            1 => array( '\\' => 2, '"'  => 0, 'def' => 1 ),
            //2 => array( '"'  => 1, "\\" => 1, 'def' => 1 ),
            2 => array( 'def' => 1 ),
            3 => array( '\\' => 4, "'"  => 0, 'def' => 3 ),
            //4 => array( "'"  => 3, "\\" => 3, 'def' => 3 ),
            4 => array( 'def' => 3 ),
            5 => array( '\\' => 6, ' '  => 0, "\t"  => 0, '=' => 7, '"' => 1, "'" => 3, 'def' => 5),
            //6 => array( '"' => 5, "'" => 5, ' ' => 5, '=' => 5, "\\" => 5, 'def' => 5 ),
            6 => array( 'def' => 5 ),
            7 => array( ' ' => 0, "\t" => 0, '"' => 1, "'" => 3, "\\" => 6, 'def' => 5),
        );
        $acs=array(
            0 => array( 6 => '+', 7 => '+', 5 => '+',),
            1 => array( 2 => '+', 0 => 'A', 1 => '+',),
            2 => array( 1 => array( '"' => '-', "\\" => '-', 'def' => '+')),
            3 => array( 4 => '+', 0 => 'A', 3 => '+',),
            4 => array( 3 => array( "'" => '-', "\\" => '-', 'def' => '+',)),
            5 => array( 0 => 'A', 7 => 'A+', 1 => 'A', 3 => 'A', 5 => '+',),
            6 => array( 5 => array( '"' => '-', "'" => '-', ' ' => '-', '=' => '-', "\\" => '-', 'def' =>'+'),),
            7 => array( 0 => 'A', 1 => 'A', 3 => 'A', 6 => 'A+', 5 => 'A+',),
        );

        $toks=array();
        $tok='';
        $state=0;
        foreach( str_split($str) as $c ) {
            $to = array_key_exists($c, $trs[$state]) ? $trs[$state][$c] : $trs[$state]['def'];
            if( array_key_exists($to, $acs[$state]) ) {
                $action=$acs[$state][$to];
                if(is_array($action)) {
                  $action = array_key_exists($c, $action) ? $action[$c] : $action['def'];
                }
                switch($action) {
                case '+'  : $tok .= $c; break;
                case '-'  : $tok = substr($tok, 0, -1).$c; break;
                case 'A'  : $toks[] = $tok; $tok=''; break;
                case 'A+' : $toks[] = $tok; $tok=$c; break;
                }
            }
            $state=$to;
        }
        /*
        if($tok != '' && ($state == 0 || $state == 5 || $state == 7))
            $toks[] = $tok;
        */
        if($tok != '') {
            if ($state == 0 || $state == 5 || $state == 7)
                $toks[] = $tok;
            else
                msg( 'In &lt;cli ...>, ignored malformed text «'.hsc($tok).'».', 2, '', '', MSG_USERS_ONLY );
        }

        return $toks;
    }

    /**
     *
     * parse params of "<cli param...>" line
     *
     * param is expected to be a blank separated list of foo[=bar]
     * statement. When there is no =bar part, then t=foo is assumed.
     * The last non assigment statement will overwrite all the others.
     * For example, for  «a=b c = "d" zorg», the returned
     * array will be ( 'a' => 'b', 'c' => 'd', 't' => 'zorg' ).
     *
     * @author Schplurtz le Déboulonné
     * @param $str String The string to tokenize
     * @return array The associative array of tokens
     */
    protected function _parseparams( $str ) {
        $toks=$this->_tokenize($str);
        $n=count($toks) ;
        $values=array( 'prompt' => false, 'continue' => false, 'comment' => false,
                       'type' => false, 'style' => '', );

        // check tokens by triplet.
        for( $i = 0; $i < $n - 2; ++$i ) {
            if( $toks[$i + 1] === '=' ) {
                $key=$this->_map($toks[$i]);
                if($key) {
                    if( $values[$key] !== false) {
                        msg( 'In &lt;cli ...>, value «'.hsc($toks[$i+2]).'» override previously defined '.hsc($key).' «'. hsc($values[$key]).'».', 2, '', '', MSG_USERS_ONLY );
                    }
                    $values[$key]=$toks[$i+2];
                }
                else {
                    msg( 'Error, unknown attribute «' . hsc($toks[$i]) . '» in &lt;cli> parametre', -1, '', '', MSG_USERS_ONLY );
                }
                $i += 2;
            }
            else {
                // if not format X = Y, add current token to style.
                $values['style'].=' '.$toks[$i];
            }
        }
        // add 1 or 2 remaining tokens to style
        for( ; $i < $n; ++$i ) {
            $values['style'].=' '.$toks[$i];
        }
        return $values;
    }

    /**
     * check <cli param names and maps them to canonical values.
     *
     * @author       Schplurtz le Déboulonné <schplurtz@laposte.net>
     * @return  Mixed    canonical attribute name or false if attr is unknown
     *                   One of 'type', 'prompt', 'continue', 'comment'.
     */
    protected function _map( $s ) {
        if( $s == 'lang' || $s == 'language' || $s == 'type' || $s == 't' || $s == 'l' || $s == 'lng' )
            return 'type';
        if( $s == 'prompt' )
            return 'prompt';
        if( $s == 'continue' || $s == 'cont' )
            return 'continue';
        if( $s == 'comment' )
            return 'comment';
        return false;
    }
}
