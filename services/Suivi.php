<?php
require_once('SmartFloreService.php');
// declare(encoding='UTF-8');
/**
 * Web service de suivi des actions smart'Flore
 *
 * @category	php 5.2
 * @package		smart-form
 * @author		Aurélien Peronnet < aurelien@tela-botanica.org>
 * @copyright	Copyright (c) 2015, Tela Botanica (accueil@tela-botanica.org)
 * @license		http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license		http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 */
class Suivi extends SmartFloreService {

	public function get($requete) {
		$evenements = $this->getEvenements(0, 100);
		epprt($evenements);
	}
}

$suivi = new Suivi();
?>