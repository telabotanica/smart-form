<?php
require_once('SmartFloreService.php');
// declare(encoding='UTF-8');
/**
 * Web service de consultation et modification des sentiers smart'Flore
 *
 * @category	php 5.2
 * @package		smart-form
 * @author		Aurélien Peronnet < aurelien@tela-botanica.org>
 * @copyright	Copyright (c) 2015, Tela Botanica (accueil@tela-botanica.org)
 * @license		http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license		http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 */
class Sentiers extends SmartFloreService {

	public function get($requete) {
		switch($requete[0]) {
			case 'sentier':
				$this->getSentiers();
				break;
			default:
				$this->error(400, "Aucune commande n'a été spécifiée");
		}
	}
	
	public function put($requete, $data) {
		switch($requete[0]) {
			case 'sentier':
				$this->ajouterSentier($data);
				break;
			case 'fiche-sentier':
				break;
			default:
				$this->error(400, "Aucune commande n'a été spécifiée");
		}
	}
	
	public function post($requete, $data) {
		
	}
	
	public function delete($requete, $data) {
		
	}
	
	private function getSentiers() {
		
		if(!empty($_GET['utilisateur'])) {
			$utilisateur = $_GET['utilisateur'];
			$champs_requete = '*, IF(value = '.$this->bdd->quote($utilisateur).', 1, 0) as sentier_utilisateur';
			$ordre = "ORDER BY sentier_utilisateur DESC";
		} else {
			$champs_requete = "*";
			$ordre ="";
		}
		
		$requete = 'SELECT '.$champs_requete.' '.
				'FROM '.$this->config['bdd']['table_prefixe'].'_triples '.
				'WHERE property = "'.$this->triple_sentier.'" '.
				$ordre;
		
		$res = $this->bdd->query($requete);
		$res = $res->fetchAll(PDO::FETCH_ASSOC);
		
		$sentiers = array();
		foreach($res as $sentier) {
			$sentiers[] = array('titre' => $sentier['resource'], 'auteur' => $sentier['value'], 'fiches' => array());
		}

		$retour = array('pagination' => array('total' => count($sentiers)), 'resultats' => $sentiers);
		
		header('Content-type: application/json');
		echo json_encode($retour);
		exit;
	}
	
	private function ajouterSentier($data) {
		
		$retour = false;
		
		if(empty($data['sentierTitre']) || empty($data['utilisateur'])) {
			$this->error('400', 'Les paramètres sentierTitre et utilisateur sont obligatoires');
		}
		
		$sentier_titre = $data['sentierTitre'];
		$utilisateur = $data['utilisateur'];
		
		$requete_existe = 'SELECT COUNT(resource) > 1 as sentier_existe '.
				'FROM '.$this->config['bdd']['table_prefixe'].'_triples '.
				'WHERE value = '.$this->bdd->quote($utilisateur).' '.
				'AND property = "'.$this->triple_sentier.'" '.
				'AND resource = '.$this->bdd->quote($sentier_titre);
		
		$res_existe = $this->bdd->query($requete_existe);
		$res_existe = $res_existe->fetch(PDO::FETCH_ASSOC);
		
		if(!$res_existe['sentier_existe']) {
		
			$requete_insertion = 'INSERT INTO '.$this->config['bdd']['table_prefixe'].'_triples '.
					'(resource, property, value) VALUES '.
					' ('.$this->bdd->quote($sentier_titre).',"'.$this->triple_sentier.'", '.$this->bdd->quote($utilisateur).') ';
				
			$res_insertion = $this->bdd->exec($requete_insertion);
			$retour = ($res_insertion !== false) ? 'OK' : false;
		} else {
			$retour = 'OK';
		}
		
		header('Content-type: text/plain');
		echo $retour;
	}	
}

$sentiers = new Sentiers();
?>