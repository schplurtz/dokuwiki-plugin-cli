<?php
/**
 * DokuWiki Plugin cli (Syntax Component)
 *
 * @license      GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author       Chris P. Jobling <C.P.Jobling@Swansea.ac.uk>
 * @author       Stephane Chazelas <stephane.chazelas@emerson.com>
 * @author       Andy Webber <dokuwiki@andywebber.com>
 * @author       Schplurtz le Déboulonné <schplurtz@laposte.net>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class syntax_plugin_cli extends DokuWiki_Syntax_Plugin {

    const PROMPT=0;
    const CONT=1;
    const COMMENT=2;
    const STYLE=array( 'font-family' => 'Bitstream Vera Sans Mono', 'font-name' => 'Bitstream Vera Sans Mono', 'background-color' => '#f7f9fa', 'border' => '0.06pt solid #8cacbb', 'font-size' => '10pt', );
    // prompt, continue and comment stack
    protected $stack;
    protected $namedpcc=array();
    protected $init=false;

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
        // DokuWiki always load and instanciate the plugin.
        // We don't want to load all this when the class is
        // loaded. Only when the syntax is really met and there
        // is need to parse should we do all this. It's not
        // even needed to render() a conversation.
        $this->stack=array(array('/^.{0,30}?[$%>#] /', '/^.{0,30}?> /', '/((^#)| #)/'));
        if(''!=($s=$this->getConf('prompt')))
            $this->stack[0][self::PROMPT]=$this->_toregexp($s);
        if(''!=($s=$this->getConf('continue')))
            $this->stack[0][self::CONT]=$this->_toregexp($s);
        if(''!=($s=$this->getConf('comment')))
            $this->stack[0][self::COMMENT]=$this->_toregexp($s, 1);
        $this->_loadnamedparam($this->getConf('namedprompt'), self::PROMPT);
        $this->_loadnamedparam($this->getConf('namedcontinue'), self::CONT);
        $this->_loadnamedparam($this->getConf('namedcomment'), self::COMMENT);
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
     * @param string $mode Parser mode
     */
    public function connectTo($mode) {
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
            $args = substr(rtrim($match), 4, -1); // strip '<cli' and '>'
            // process args to CLI tag: sets $comment_str and $prompt_str and $prompt_cont
            $name=$this->_extract($args, '(?:t|l|lng|language|lang|type)');
            $this->current=array();
            $this->current[self::PROMPT]=($s = $this->_extract($args, 'prompt')) ?
                $this->_toregexp($s)
                : (($t=$this->namedpcc[$name][self::PROMPT]) ?
                    $t
                    : $this->stack[0][self::PROMPT]
                );
            $this->current[self::CONT]=($s = $this->_extract($args, 'cont(?:inue)?')) ?
                $this->_toregexp($s)
                : (($t=$this->namedpcc[$name][self::CONT]) ?
                    $t
                    : $this->stack[0][self::CONT]
                );
            $this->current[self::COMMENT]=($s = $this->_extract($args, 'comment')) ?
                $this->_toregexp($s,1)
                : (($t=$this->namedpcc[$name][self::COMMENT]) ?
                    $t
                    : $this->stack[0][self::COMMENT]
                );
            $this->stack[]=$this->current;
            // return nesting level
            return array($state, count($this->stack) - 2);
        case DOKU_LEXER_UNMATCHED :
            return array( $state, $this->_parse_conversation($match) );
        case DOKU_LEXER_EXIT :
              array_pop($this->stack);
              $this->current=end($this->stack);
              // return same nested level as DOKU_LEXER_ENTER
              return array($state, count($this->stack) -1);
        }
        return array();
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
     * @param  $matches String   The current recognised prompt
     * @return String[]          the 3 components of the line : prompt, command, comment
     */
    protected function _parseline( $line, $prompt ) {
        $comment='';
        $index=strlen($prompt);
        //$prompt = substr($line, 0, $index);
        $comcom = substr( $line, $index );
        $ar=preg_split($this->current[self::COMMENT], $comcom, 2, PREG_SPLIT_DELIM_CAPTURE);
        if( isset($ar[1]) ) {
            $comment=$ar[1].end($ar);
        }
        $ret=array( $prompt, $ar[0], $comment );
        return $ret;
    }

    /**
     * Render output. Call specialized methods.
     *
     * @param string         $mode      Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer  $renderer  The renderer
     * @param array          $data      The data from the handler() function
     * @return bool If rendering was successful.
     */
    public function render($mode, Doku_Renderer $renderer, $data) {
        if($mode == 'xhtml'){
            $this->_render_xhtml( $renderer, $data );
             return true;
        }
        elseif( $mode == 'odt' || $mode == 'odt_pdf' ) {
            $this->_render_odt( $renderer, $data );
            return true;
        }
        return false;
    }

    /**
     * render conversation as xhtml
     *
     * @author Chris P. Jobling <C.P.Jobling@Swansea.ac.uk>
     * @author Schplurtz le Déboulonné <Schplurtz@laposte.net>
     * @param  $renderer Doku_Renderer   a renderer object
     * @param  $data     mixed[]         associated data
     * @return void
     */
    protected function _render_xhtml(Doku_Renderer $renderer, $data) {
        list($state, $thing) = $data;
        switch ($state) {
        case DOKU_LEXER_ENTER :
            // $thing is nesting level here.
            // only create one <pre> element for all the nested cli
            if( 0 === $thing )
                $renderer->doc .= '</p><pre class="cli code">';
            else
                $renderer->doc .= DOKU_LF;
        break;
        case DOKU_LEXER_UNMATCHED :
            // Here $thing is an array of parsed lines.
            $not_first_line=false;
            foreach( $thing as $line ) {
                if($not_first_line)
                    $renderer->doc .= DOKU_LF;
                else
                    $not_first_line=true;
                if(is_array($line)) {
                    $renderer->doc .= '<span class="cli_prompt">' . $renderer->_xmlEntities($line[0]) . "</span>";
                    if( '' != $line[1] )
                        $renderer->doc .= '<span class="cli_command">' . $renderer->_xmlEntities($line[1]) . "</span>";
                    if( '' != $line[2] )
                        $renderer->doc .= '<span class="cli_comment">' . $renderer->_xmlEntities($line[2]) . "</span>";
                } else {
                    $renderer->doc .= '<span class="cli_output">' . $renderer->_xmlEntities($line) . "</span>";
                }
            }
        break;
        case DOKU_LEXER_EXIT :
            // $thing is nesting level here.
            // only close <pre> if we're closing the outermost <cli>
            if( 0 === $thing )
                $renderer->doc .= "</pre><p>";
            else
                $renderer->doc .= DOKU_LF;
        break;
        }
    }
    /**
     * render conversation as odt.
     *
     * @author Schplurtz le Déboulonné <Schplurtz@laposte.net>
     * @param  $renderer Doku_Renderer   a renderer object
     * @param  $data     mixed[]         associated data
     * @return void
     */
    protected function _render_odt(Doku_Renderer $renderer, $data) {
        /*
         * Because of bug and lack of styling option in generateSpansfromHTMLCode()
         * and because preformattedtext() does not allow to select some style,
         * I have to do it myself. Problem is that although I select a monospace
         * font in my style, multiple spaces are still replaced as one single
         * space.
         *
         * The hackish solution is to convert sequences of 2 ordinary spaces to
         * sequences of space+nbspace (\u0020\u00A0). so for example this string
         * '       ' will result in ' _ _ _ ' where _ stands for nbspace \u00A0.
         * libreoffice/xml/whatever can't replace the spaces in this situation.
         *
         * I don't expect this hack to break soon, or ever, since it relies on
         * the very nature of the nbspace char.
         *
         * Schplurtz rulez !
         */
        list($state, $thing) = $data;
        switch ($state) {
        case DOKU_LEXER_ENTER :
            // $thing is nesting level here.
            if( 0 === $thing ) {
                // Just open once. nested cli do not need to reopen
                $renderer->p_close();
                $renderer->_odtParagraphOpenUseProperties(self::STYLE);
            }
            else {
                $renderer->linebreak();
            }
        break;
        case DOKU_LEXER_UNMATCHED :
            // here $thing is an array of parsed lines.
            $spnbsp="  "; // ! You might not notice, the second space is really \u00A0
            $not_first_line=false;
            foreach( $thing as $line ) {
                if($not_first_line)
                    $renderer->linebreak();
                else
                    $not_first_line=true;
                if(is_array($line)) {
                    $renderer->_odtSpanOpenUseProperties(array('color' => 'green'));
                    $renderer->cdata(str_replace('  ', $spnbsp, $line[0]));
                    $renderer->_odtSpanClose();
                    if( '' != $line[1] ) {
                        $renderer->_odtSpanOpenUseProperties(array('color' => 'red'));
                        $renderer->cdata(str_replace('  ', $spnbsp, $line[1]));
                        $renderer->_odtSpanClose();
                    }
                    if( '' != $line[2] ) {
                        $renderer->_odtSpanOpenUseProperties(array('color' => 'brown'));
                        $renderer->cdata(str_replace('  ', $spnbsp, $line[2]));
                        $renderer->_odtSpanClose();
                    }
                } else {
                    $renderer->_odtSpanOpenUseProperties(array('color' => 'blue'));
                    $renderer->cdata(str_replace('  ', $spnbsp, $line));
                    $renderer->_odtSpanClose();
                }
            }
        break;
        case DOKU_LEXER_EXIT :
            // thing is nesting level here.
            if( 0 === $thing ) {
                $renderer->p_close();
            }
            else {
                $renderer->linebreak();
            }
        break;
        }
    }
    /**
     * load named prompts from config
     *
     * @author Schplurtz le Déboulonné <Schplurtz@laposte.net>
     * @param  $s             String        The configuration value
     * @param  $type          Int           The index of the config
     * @return void
     */
    protected function _loadnamedparam($s, $type) {
        foreach(preg_split('/\n\r|\n|\r/',$s) as $line){
            if(''==$line)
                continue;
            list($nom,$val)=explode(':', $line, 2);
            $this->namedpcc[$nom][$type]=($type == self::COMMENT) ? $this->_toregexp($val,1) : $this->_toregexp($val);
        }
    }
    /**
     * extract value of attribute foo=value from string.
     *
     * removes surrounding simple or double quotes and unescape value
     *
     * @author Stephane Chazelas <stephane.chazelas@emerson.com>
     * @param  $args          String        The string to search
     * @param  $param         String        The attribute to find.
     * @return mixed                        the value of attribute $param or null if not found or empty
     */
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

    /**
     * transform a string or regexp into a regexp.
     *
     * The string is to match either a prompt
     * or a comment, and is thus anchored accordingly.
     *
     * @author Schplurtz le Déboulonné <Schplurtz@laposte.net>
     * @param  $s             String        The string to transform
     * @param  $is_comment_re Int           1 the re is going to match a comment, 0 otherwise
     * @return String                       The regexp.
     */
    function _toregexp( $s, $is_comment_re=0 ) {
        if(preg_match('/^([\/=,;%@]).+(\1)$/', $s)) {
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
     * expands tabs to spaces.
     *
     * Schplurtz says : bug warning : if $line contains ascii 7 (\a bell) and $nbspace
     * is set to true, then existing \a will turn to &nbsp;
     *
     * @author dev-null-dweller https://stackoverflow.com/users/258674/dev-null-dweller
     * @param   $line           String      The string with tabs to expand
     * @param   $tab            Integer     tab length, default 4
     * @param   $nbsp           Boolean     whether to convert spaces to '&nbsp;'. default is false.
     * @return  String                      The string with expanded tabs
     */
    /*
    protected function _tab2space($line, $tab = 4, $nbsp = FALSE) {
        while (($t = mb_strpos($line,"\t")) !== FALSE) {
            $preTab = $t?mb_substr($line, 0, $t):'';
            $line = $preTab . str_repeat($nbsp?chr(7):' ', $tab-(mb_strlen($preTab)%$tab)) . mb_substr($line, $t+1);
        }
        return  $nbsp?str_replace($nbsp?chr(7):' ', '&nbsp;', $line):$line;
    }
    */
}

// vim:ts=4:sw=4:et:
