<?php
require_once('SmartFloreService.php');
// declare(encoding='UTF-8');
/**
 * Web service de consultation des triples smart'Flore
 *
 * @category	php 5.2
 * @package		smart-form
 * @author		Aurélien Peronnet < aurelien@tela-botanica.org>
 * @copyright	Copyright (c) 2015, Tela Botanica (accueil@tela-botanica.org)
 * @license		http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license		http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 */
class Triples extends SmartFloreService {

	function get($requete) {
		// https://beta.tela-botanica.org/smart-form/services/Triples.php/user/{old_email}/change-address-to/{new_email}
		if ('user' === $requete[0] && 'change-address-to' === $requete[2]) {
			$this->verifierAuthentification();
			if (!$this->estAdmin()) {
				$this->error(400, "Faut être admin lel");
			}

			$this->modifAuteurEmail($requete);
		} else {
			$this->error(400, "Aucune commande connue n'a été spécifiée");
		}
	}

	function modifAuteurEmail($requete) {
		$ancien_mail = $requete[1];
		$nouveau_mail = $requete[3];

		// modif value (sentier et activité)
		$requete_modification_value = 'UPDATE '.$this->config['bdd']['table_prefixe'].'_triples '.
				' SET value = REPLACE(value, '.$this->bdd->quote($ancien_mail).', '.$this->bdd->quote($nouveau_mail).')'.
				' WHERE value LIKE '.$this->bdd->quote('%'.$ancien_mail.'%');
		// modif resource (favoris)
		$requete_modification_resource = 'UPDATE '.$this->config['bdd']['table_prefixe'].'_triples '.
			' SET resource = REPLACE(resource, '.$this->bdd->quote($ancien_mail).', '.$this->bdd->quote($nouveau_mail).')'.
			' WHERE resource LIKE '.$this->bdd->quote('%'.$ancien_mail.'%');

		$res_modification = $this->bdd->exec($requete_modification_value);
		$this->bdd->exec($requete_modification_resource);

		$retour = ($res_modification !== false) ? 'OK' : false;

		if ($retour == 'OK') {
			$utilisateur = $this->utilisateur['courriel'];
			$infos_evenement = array(
				'utilisateur' => $utilisateur,
				'ancien_mail' => str_replace('@', '[at]', $ancien_mail),
				'nouveau_mail' => str_replace('@', '[at]', $nouveau_mail)
			);
			// Enregistrement de l'évènement
			$this->enregistrerEvenement($this->triple_evenement_changement_email, $infos_evenement);
		}

		header('Content-type: text/plain');
		echo $retour;
	}
}

$triples = new Triples();
