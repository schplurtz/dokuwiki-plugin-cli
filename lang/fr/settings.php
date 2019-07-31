<?php
/**
 * French language file for cli plugin
 *
 * @author Schplurtz le Déboulonné <schplurtz@laposte.net>
 */

// keys need to match the config setting name
$lang['prompt']='Expression régulière (délimitée par des / = , ; % ou @) ou chaine décrivant l\'invite de commande principale de l\'interface en ligne de commande. La valeur ‘/^.{0,30}?[$%&gt;#] /’ est la valeur par défaut. Elle décrit le plus petit texte commençant au début de la ligne d\'un maximum de 30 caractères et se terminant par un signe $, %, &gt;, # suivi d\'un espace, et convient à une majorité d\'interfaces, y compris les shells<br />Si vous utilisez les valeurs par défaut et que vos invites principale et secondaire se terminent toutes les deux par ‘&gt; ’, il faudra changer cette expression pour la rendre plus spécifique, car elle va reconnaitre les deux invites comme invite principale';
$lang['continue']='Expression régulière (délimitée par des / = , ; % ou @) ou chaine décrivant l\'invite secondaire. La valeur par défaut est ‘/^.{0,30}?&gt; /’ le plus petit texte d\'au plus trente caractères se terminant par ‘&gt; ’.';
$lang['comment']='Chaine ou expression régulière marquant le début d\'un commentaire. la valeur par défaut est ‘/(^#)| #/’. Cela correspond à un # en début de ligne ou alors un espace suivi d\'un #.';
$lang['namedprompt']='liste d\'invites nommées, une par ligne, sous la forme "nom:expression-régulière ou chaine"<br />Le nom peut ensuite être utilisé dans une page de cette manière :<br /><tt>&lt;cli t=nom&gt;</tt><br />ce qui est plus court que<br /><tt>&lt;cli prompt="blabla" continue="blibli" comment="zap"&gt;</tt>';
$lang['namedcontinue']='Liste d\'invites secondaires nommées, une par ligne, sous la forme "nom:expression-régulière ou chaine".';
$lang['namedcomment']='liste de commentaire nommés, un par ligne, sous la forme "expression-régulière ou chaine"';
$lang['odtbackground']  = 'Pour l\'export en ODT ou PDF via le greffon <a href="https://www.dokuwiki.org/plugin:odt">odt</a>, couleur du fond pour les blocs <tt>&lt;cli></tt>. La valeur par défaut <tt>#f7f9fa</tt> est la même que celle des blocs <tt>&lt;code></tt>.';
$lang['odtborderwidth'] = 'Pour l\'export en ODT ou PDF via le greffon <a href="https://www.dokuwiki.org/plugin:odt">odt</a>, largeur dans une <a href="https://developer.mozilla.org/fr/docs/Web/CSS/length">unité de distance CSS valable</a>, généralement le point «pt», de la bordure des blocs <tt>&lt;cli></tt>. Valeur par défaut : 0.06pt';
$lang['odtbordercolor'] = 'Pour l\'export en ODT ou PDF via le greffon <a href="https://www.dokuwiki.org/plugin:odt">odt</a>, couleur de la bodure des bloc <tt>&lt;cli></tt>. Valeur par défaut #8cacbb';


//Setup VIM: ex: et ts=4 :
