<?php
require_once('SmartFloreService.php');
// declare(encoding='UTF-8');
/**
 * Web service de consultation et modification des liste de page favorites smart'Flore
 *
 * @category	php 5.2
 * @package		smart-form
 * @author		Aurélien Peronnet < aurelien@tela-botanica.org>
 * @copyright	Copyright (c) 2015, Tela Botanica (accueil@tela-botanica.org)
 * @license		http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license		http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 */
class Favoris extends SmartFloreService {

	public function get($requete) {
		$this->getFavoris();
	}

	public function put($requete, $data) {
		$this->ajouterFavoris($data);
	}

	public function delete($requete, $data) {
		$this->supprimerFavoris($data);
	}

	public function getFavoris() {

		$this->verifierAuthentification();
		$utilisateur = $this->utilisateur['courriel'];

		$favoris = $this->getFavorisPourUtilisateur($utilisateur);

		$recherche = array('noms_pages' => array(), 'debut' => 0, 'limite' => 100);
		foreach($favoris as $page) {
			$recherche['noms_pages'][] = $this->bdd->quote($page['resource']);
		}

		$pages_wiki = $this->getPagesWikiParRechercheExacte($recherche);
		$pages_completees_par_infos_taxon = $this->completerPagesParInfosTaxon($pages_wiki[0]);
		$retour = array('pagination' => array('total' => $pages_wiki[1]), 'resultats' => array_values($pages_completees_par_infos_taxon['resultats']));

		header('Content-type: application/json');
		echo json_encode($retour);
		exit;
	}

	public function ajouterFavoris($data) {

		$retour = false;

		if(empty($data['pageTag'])) {
			$this->error('400', 'Le paramètre pageTag est obligatoire');
		}

		$page_tag = $data['pageTag'];
		$utilisateur = $this->utilisateur['courriel'];

		$requete_existe = 'SELECT COUNT(resource) >= 1 as favoris_existe '.
				'FROM '.$this->config['bdd']['table_prefixe'].'_triples '.
				'WHERE value = '.$this->bdd->quote($utilisateur).' '.
				'AND property = "'.$this->triple_favoris_fiche.'" '.
				'AND resource = '.$this->bdd->quote($page_tag);

		$res_existe = $this->bdd->query($requete_existe);
		$res_existe = $res_existe->fetch(PDO::FETCH_ASSOC);

		if(!$res_existe['favoris_existe']) {

			$requete_insertion = 'INSERT INTO '.$this->config['bdd']['table_prefixe'].'_triples '.
					'(resource, property, value) VALUES '.
					' ('.$this->bdd->quote($page_tag).',"'.$this->triple_favoris_fiche.'", '.$this->bdd->quote($utilisateur).') ';

			$res_insertion = $this->bdd->exec($requete_insertion);
			$retour = ($res_insertion !== false) ? 'OK' : false;

		} else {
			$retour = 'OK';
		}

		header('Content-type: text/plain');
		echo $retour;
	}

	public function supprimerFavoris($data) {
		$retour = false;

		if(empty($data['pageTag'])) {
			$this->error('400', 'Le paramètres pageTag est obligatoire');
		}

		$page_tag = $data['pageTag'];
		$utilisateur = $this->utilisateur['courriel'];

		$requete_suppression = 'DELETE FROM '.$this->config['bdd']['table_prefixe'].'_triples '.
				'WHERE resource = '.$this->bdd->quote($page_tag).' AND '.
				'property = "'.$this->triple_favoris_fiche.'" AND '.
				'value = '.$this->bdd->quote($utilisateur);

		$res_suppression = $this->bdd->exec($requete_suppression);
		$retour = ($res_suppression !== false) ? 'OK' : false;

		header('Content-type: text/plain');
		echo $retour;
	}
}

$favoris = new Favoris();
?>
