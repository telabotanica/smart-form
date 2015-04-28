<?php
require_once('SmartFloreService.php');
// declare(encoding='UTF-8');
/**
 * Web service de consultation des pages smart'Flore
 *
 * @category	php 5.2
 * @package		smart-form
 * @author		Aurélien Peronnet < aurelien@tela-botanica.org>
 * @copyright	Copyright (c) 2011, Tela Botanica (accueil@tela-botanica.org)
 * @license		http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license		http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 */
class Pages extends SmartFloreService {
	
	function get($requete) {
		$this->getPages();
	}
	
	function getPages() {	
		$recherche = array();
		
		$recherche['debut'] = !empty($_GET['debut']) ? intval($_GET['debut']) : 0;
		$recherche['limite'] = !empty($_GET['limite']) ? intval($_GET['limite']) : 20;
		
		$recherche['referentiel'] = !empty($_GET['referentiel']) ? $_GET['referentiel'] : '%';
		$recherche['num_tax'] = !empty($_GET['num_tax']) ? $_GET['num_tax'] : '%';
		
		$recherche['recherche'] = !empty($_GET['recherche']) ? $_GET['recherche'] : '%';
		
		$recherche['pages_existantes'] = isset($_GET['pages_existantes']) ? $_GET['pages_existantes'] == 'true' : false;
		
		if($recherche['pages_existantes']) {
			$retour = $this->getPagesExistantes($recherche);
		} else {
			$retour = $this->getPagesToutes($recherche);
		}
		
		header('Content-type: application/json');
		echo json_encode($retour);
		exit;
	}
	
	function getPagesExistantes($recherche) {
		
		$recherche['noms_pages'] = 'SmartFlore'.$recherche['referentiel'].'nt'.$recherche['num_tax'];	
		list($pages_wiki, $nb_pages) = $this->getPagesWikiParRechercheFloue($recherche);
				
		$retour = array('pagination' => array('total' => $nb_pages), 'resultats' => array());
		
		$infos_indexees_par_nt = $this->completerPagesParInfosTaxon($pages_wiki);
		
		$this->bdd = null;
		$retour['resultats'] = array_values($infos_indexees_par_nt);
		
		return $retour;
	}
	
	function getPagesToutes($recherche) {	
		
		$retour = array('pagination' => array('total' => 0), 'resultats' => array());
		
		$url_eflore_tpl = $this->config['eflore']['recherche_noms_url'];
		$url = sprintf($url_eflore_tpl, strtolower($recherche['referentiel']), urlencode($recherche['recherche'].'%'), $recherche['debut'], $recherche['limite']);

		$infos = @file_get_contents($url);
		$infos = json_decode($infos, true);
		
		if(!empty($infos['entete'])) {
			
			$retour['pagination']['total'] = $infos['entete']['total'];
			$noms_pages = array();
			$num_tax_a_nums_noms = array();
			
			foreach($infos['resultat'] as &$nom) {
				if(!empty($nom['num_taxonomique']) && $nom['num_taxonomique'] != 0) {
					$num_nom = $nom['id'];
					$num_tax_a_nums_noms[$nom['num_taxonomique']][] = $num_nom;
					
					$nom_page = 'SmartFlore'.$recherche['referentiel'].'nt'.$nom['num_taxonomique'];
					// le faire maintenant nous fait économiser un array_map plus tard
					$noms_pages[] = $this->bdd->quote($nom_page);
					
					$retour['resultats'][$num_nom] = array(
						'existe' => false,
						'tag' => $nom_page,
						'time' => '',
						'owner' => '',
						'user' => '',
						'nb_revisions' => 0,
						'infos_taxon' => array(
								"num_taxonomique" => $nom['num_taxonomique'],
								"nom_sci"=> $nom['nom_sci'],
								"nom_sci_complet" => $nom['nom_sci_complet'],
								"retenu" => $nom['retenu'],
								"num_nom" => $nom['id'],
								"referentiel" => $recherche['referentiel']
						)
					);
				}
			}
				
			$recherche['noms_pages'] = $noms_pages;
			list($pages_wiki, $nb_pages_wiki) = $this->getPagesWikiParRechercheExacte($recherche);
			
			foreach($pages_wiki as $page_wiki) {
				list($referentiel, $nt) = $this->splitNt($page_wiki['tag']);
				foreach($num_tax_a_nums_noms[$nt] as $num_nom_fiche) {
					$retour['resultats'][$num_nom_fiche] = array_merge($retour['resultats'][$num_nom_fiche], $page_wiki);
					$retour['resultats'][$num_nom_fiche]['existe'] = true;
				}
			}	
		}
		
		$retour['resultats'] = array_values($retour['resultats']);
		return $retour;
	}
}

$pages = new Pages();
?>