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
			case 'sentier-fiche':
				$this->getFichesASentier();
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
			case 'sentier-fiche':
				$this->ajouterFicheASentier($data);
				break;
			default:
				$this->error(400, "Aucune commande n'a été spécifiée");
		}
	}

	public function post($requete, $data) {

	}

	public function delete($requete, $data) {
		switch($requete[0]) {
			case 'sentier':
				$this->supprimerSentier($data);
				break;
			case 'sentier-fiche':
				$this->supprimerFicheASentier($data);
				break;
			default:
				$this->error(400, "Aucune commande n'a été spécifiée");
		}
	}

	private function getSentiers() {

		$this->verifierAuthentification();
		$utilisateur = $this->utilisateur['nomWiki'];
		if(!empty($utilisateur)) {
			// permet de renvoyer les sentiers de l'utilisateur en premier
			// si celui ci est précisé
			$champs_requete = '*, IF(value = '.$this->bdd->quote($utilisateur).', 1, 0) as sentier_utilisateur';
			$ordre = "ORDER BY sentier_utilisateur DESC, resource ASC";
		} else {
			$champs_requete = "*";
			$ordre ="";
		}

		$requete = 'SELECT '.$champs_requete.' '.
				'FROM '.$this->config['bdd']['table_prefixe'].'_triples '.
				'WHERE property = "'.$this->triple_sentier.'" '.
				(!empty($utilisateur) ? 'AND value = '.$this->bdd->quote($utilisateur) : '');

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

		if (empty($data['sentierTitre'])) {
			$this->error('400', 'Le paramètre sentierTitre est obligatoire');
		}

		$sentier_titre = $data['sentierTitre'];
		$utilisateur = $this->utilisateur['nomWiki'];
		$utilisateurCourriel = $this->utilisateur['courriel'];

		$requete_existe = 'SELECT COUNT(resource) >= 1 as sentier_existe '.
				'FROM '.$this->config['bdd']['table_prefixe'].'_triples '.
				'WHERE property = "'.$this->triple_sentier.'" '.
				'AND TRIM(resource) = '.$this->bdd->quote(trim($sentier_titre));

		$res_existe = $this->bdd->query($requete_existe);
		$res_existe = $res_existe->fetch(PDO::FETCH_ASSOC);

		if(!$res_existe['sentier_existe']) {

			$requete_insertion = 'INSERT INTO '.$this->config['bdd']['table_prefixe'].'_triples '.
					'(resource, property, value) VALUES '.
					' ('.$this->bdd->quote($sentier_titre).',"'.$this->triple_sentier.'", '.$this->bdd->quote($utilisateur).') ';

			$res_insertion = $this->bdd->exec($requete_insertion);
			$retour = ($res_insertion !== false) ? 'OK' : false;

			if($retour == 'OK') {
				$infos_evenement = array('utilisateur' => $utilisateur, 'utilisateur_courriel' => $utilisateurCourriel, 'titre' => $sentier_titre);
				// Enregistrement de l'évènement pour des stats ultérieures
				$this->enregistrerEvenement($this->triple_evenement_sentier_ajout, $infos_evenement);
			}
		} else {
			$retour = $this->error('400', 'Un sentier portant ce nom a déjà été saisi');
		}

		header('Content-type: text/plain');
		echo $retour;
	}

	private function supprimerSentier($data) {

		$retour = false;

		if (empty($data['sentierTitre'])) {
			$this->error('400', 'Le paramètre sentierTitre est obligatoire');
		}

		$sentier_titre = $data['sentierTitre'];
		$utilisateur = $this->utilisateur['nomWiki'];

		$requete_suppression = 'DELETE FROM '.$this->config['bdd']['table_prefixe'].'_triples '.
				'WHERE value = '.$this->bdd->quote($utilisateur).' '.
				'AND property = "'.$this->triple_sentier.'" '.
				'AND resource = '.$this->bdd->quote($sentier_titre);

		$res_suppression = $this->bdd->exec($requete_suppression);

		// Supprimer également les liens des fiches appartenant au sentier (et pas les fiches elles mêmes)
		$requete_suppression_fiches = 'DELETE FROM '.$this->config['bdd']['table_prefixe'].'_triples '.
				'WHERE value = '.$this->bdd->quote($sentier_titre).' '.
				'AND property = "'.$this->triple_sentier_fiche.'"';

		$res_suppression_fiches = $this->bdd->exec($requete_suppression_fiches);

		$retour = ($res_suppression !== false) && ($res_suppression_fiches !== false) ? 'OK' : false;

		header('Content-type: text/plain');
		echo $retour;
	}

	private function getFichesASentier() {

		if(empty($_GET['sentierTitre'])) {
			$this->error('400', 'Le paramètre sentierTitre est obligatoire');
		}

		$sentier_titre = $_GET['sentierTitre'];

		$requete = 'SELECT * FROM '.$this->config['bdd']['table_prefixe'].'_triples '.
				'WHERE property = "'.$this->triple_sentier_fiche.'" '.
				'AND value = '.$this->bdd->quote($sentier_titre);

		$res = $this->bdd->query($requete);
		$res = $res->fetchAll(PDO::FETCH_ASSOC);

		$sentiers_a_fiches = array('noms_pages' => array(), 'debut' => null, 'limite' => null);

		$pages_a_traiter = array();
		foreach($res as $sentier) {
			$sentiers_a_fiches['noms_pages'][] = $this->bdd->quote($sentier['resource']);
			// Certaines fiches ajoutées à des sentiers n'existent pas forcément
			// donc on crée manuellement leur entrée de tableau pour qu'elles soient
			// tout de même augmentées des infos taxonomiques et renvoyées
			$pages_a_traiter[$sentier['resource']] = array(
						'existe' => false,
						'favoris' => false,
						'tag' => $sentier['resource'],
						'time' => '',
						'owner' => '',
						'user' => '',
						'nb_revisions' => 0,
						'infos_taxon' => array()
				);
		}

		$sentiers = array();
		$nb_sentiers = 0;
		if(!empty($sentiers_a_fiches['noms_pages'])) {
			list($pages, $nb_sentiers) = $this->getPagesWikiParRechercheExacte($sentiers_a_fiches);
			// affectation de leurs informations aux pages existantes
			foreach($pages as $page) {
				$pages_a_traiter[$page['tag']] = $page;
			}

			$pages_enrichies = $this->completerPagesParInfosTaxon(array_values($pages_a_traiter));
			// $pages_enrichies['resultats'] est indexé par referentiel.num_nom pour des raisons pratiques de
			// tri et d'accès, on désindexe avant de renvoyer les résultats
			$sentiers = array_values($pages_enrichies['resultats']);
			usort($sentiers, array($this, 'trierParNomSci'));
			unset($sentiers['fiches_a_num_nom']);
		}

		$retour = array('pagination' => array('total' => $nb_sentiers), 'resultats' => $sentiers);

		header('Content-type: application/json');
		echo json_encode($retour);
		exit;
	}

	private function trierParNomSci($a, $b) {
		$a1 = $a;
		if(empty($a1['infos_taxon'])) {
			$a1['infos_taxon']['nom_sci'] = '';
		}

		$b1 = $b;
		if(empty($b1['infos_taxon'])) {
			$b1['infos_taxon']['nom_sci'] = '';
		}

		return strcasecmp($a1['infos_taxon']['nom_sci'], $b1['infos_taxon']['nom_sci']);
	}

	private function ajouterFicheASentier($data) {

		$retour = false;

		if(empty($data['sentierTitre']) || empty($data['pageTag'])) {
			$this->error('400', 'Les paramètres sentierTitre et pageTag sont obligatoires');
		}

		$sentier_titre = $data['sentierTitre'];
		$utilisateur = $this->utilisateur['nomWiki'];
		$page_tag = $data['pageTag'];

		$requete_existe = 'SELECT COUNT(resource) > 1 as sentier_a_page_existe '.
				'FROM '.$this->config['bdd']['table_prefixe'].'_triples '.
				'WHERE value = '.$this->bdd->quote($sentier_titre).' '.
				'AND property = "'.$this->triple_sentier_fiche.'" '.
				'AND resource = '.$this->bdd->quote($page_tag);

		$res_existe = $this->bdd->query($requete_existe);
		$res_existe = $res_existe->fetch(PDO::FETCH_ASSOC);

		if(!$res_existe['sentier_a_page_existe']) {

			$requete_insertion = 'INSERT INTO '.$this->config['bdd']['table_prefixe'].'_triples '.
					'(resource, property, value) VALUES '.
					' ('.$this->bdd->quote($page_tag).',"'.$this->triple_sentier_fiche.'", '.$this->bdd->quote($sentier_titre).') ';

			$res_insertion = $this->bdd->exec($requete_insertion);
			$retour = ($res_insertion !== false) ? 'OK' : false;

		} else {
			$retour = 'OK';
		}

		header('Content-type: text/plain');
		echo $retour;
	}

	private function supprimerFicheASentier($data) {

		$retour = false;

		if(empty($data['sentierTitre']) || empty($data['pageTag'])) {
			$this->error('400', 'Les paramètres sentierTitre et pageTag sont obligatoires');
		}

		$sentier_titre = $data['sentierTitre'];
		$page_tag = $data['pageTag'];

		$requete_suppression = 'DELETE FROM '.$this->config['bdd']['table_prefixe'].'_triples '.
				'WHERE value = '.$this->bdd->quote($sentier_titre).' '.
				'AND property = "'.$this->triple_sentier_fiche.'" '.
				'AND resource = '.$this->bdd->quote($page_tag);

		$res_suppression = $this->bdd->exec($requete_suppression);
		$retour = ($res_suppression !== false) ? 'OK' : false;

		header('Content-type: text/plain');
		echo $retour;
	}
}

$sentiers = new Sentiers();
?>
