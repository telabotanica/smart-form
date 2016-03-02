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

		$url = "http".(!empty($_SERVER['HTTPS'])?"s":"").
		"://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

		$infos = array(
					'titre' => "Flux des sentiers Smart'Flore",
					'lien_smartflore' => $this->config['smartflore']['application_saisie_url'],
					'lien_service' => $url,
					'description' => "Flux de suivi des sentiers botaniques créés par l'application Smart'Flore",
					'items' => array()
				);

		foreach ($evenements as $evt) {
			$infos['items'][] = $this->construireItem($evt);
		}

		ob_start();
		extract($infos);
		include(dirname(__FILE__).'/squelettes/rss2.tpl.xml');
		$rss = ob_get_clean();

		header('Content-type: text/xml');
		echo $rss;
		exit;
	}

	protected function construireItem($evt) {

		$infos_sentier = json_decode($evt['value'], true);
		$description = "Sentier Smart'Flore ".$infos_sentier['titre']." ajouté par ".$infos_sentier['utilisateur']." (".$infos_sentier['utilisateur_courriel'].")";

		$item = array(
					'guid' => 'http://www.tela-botanica.org/smartFlore/'.$evt['property'].'/'.$evt['id'],
			  		'titre' => 'Ajout du sentier '.$infos_sentier['titre'].' par '.$infos_sentier['utilisateur'],
			 		'lien' => $this->config['smartflore']['application_saisie_url'],
			  		'description_encodee' => $description,
			 		'categorie' => "Sentier Smart'Flore",
			  		'date_maj_RSS' => date("r", strtotime($evt["resource"]))
				);

		return $item;
	}
}

$suivi = new Suivi();
?>
