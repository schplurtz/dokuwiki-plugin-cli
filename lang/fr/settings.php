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


//Setup VIM: ex: et ts=4 :
