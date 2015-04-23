<?php
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
class Pages {
	
	private $config = null;
	private $bdd = null;
	
	public function __construct() {		
		$this->config = parse_ini_file('config.ini', true);		
		try {
			$this->bdd = new PDO('mysql:host='. $this->config['bdd']['host'].';dbname='. $this->config['bdd']['db'], $this->config['bdd']['user'],  $this->config['bdd']['pass']);
			$this->bdd->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$this->bdd->exec("SET CHARACTER SET utf8");
		}
		catch (PDOException $e) {
			$error_message = $e->getMessage();
			echo "this is displayed because an error was found";
			exit();
		}
	}
	
	function splitNt($page) {
		$page = str_replace('SmartFlore', '', $page);
		return split('nt', $page);
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
		
		$infos_indexees_par_nt = array();
		$nts = array();
		foreach($pages_wiki as $resultat) {
			list($referentiel, $nt) = $this->splitNt($resultat['tag']);
			if(empty($nts)) {
				$nts[$referentiel] = array();
			}
			$nts[$referentiel][] = $nt;
			$resultat['existe'] = true;
			$infos_indexees_par_nt[$referentiel.$nt] = $resultat;
		}
		
		$retour = array('pagination' => array('total' => $nb_pages), 'resultats' => array());
		
		$url_eflore_tpl = $this->config['eflore']['infos_taxons_url'];
		
		foreach($nts as $referentiel => $nts_a_ref) {
			if(!empty($referentiel)) {
				$nts_ref_tranches = array_chunk($nts_a_ref, 99, true);
				foreach($nts_ref_tranches as $tranche) {
		
					$url = sprintf($url_eflore_tpl, strtolower($referentiel), implode(',', $tranche));
					$infos = file_get_contents($url);
					$infos = json_decode($infos, true);
		
					foreach($infos['resultat'] as $num_nom => $infos_a_nt) {
						$infos_a_nt['num_nom'] = $num_nom;
						$infos_a_nt['referentiel'] = $referentiel;
						$infos_indexees_par_nt[$referentiel.$infos_a_nt['num_taxonomique']]['infos_taxon'] = $infos_a_nt;
					}
				}
			}
		}
		
		$this->bdd = null;
		$retour['resultats'] = array_values($infos_indexees_par_nt);
		
		return $retour;
	}
	
	function getPagesWikiParRechercheFloue($recherche) {
		$tpl_quote = $this->bdd->quote($recherche['noms_pages']);
		return $this->getPagesWiki('tag LIKE '.$tpl_quote.' ', $recherche['debut'], $recherche['limite']);
	}
	
	function getPagesWikiParRechercheExacte($recherche) {
		return $this->getPagesWiki('tag IN ('.implode(',', $recherche['noms_pages']).')', $recherche['debut'], $recherche['limite']);
	}
	
	private function getPagesWiki($condition, $debut, $limite) {
		
		$champs = "id, tag, time, owner, user, latest";
		
		$requete = 'SELECT '.$champs.', COUNT(tag) as nb_revisions '.
				'FROM `eFloreRedaction_pages` '.
				'WHERE '.$condition.' '.
				'GROUP BY tag '.
				'ORDER BY nb_revisions DESC '.
				'LIMIT '.$debut.', '.$limite;
		
		$res = $this->bdd->query($requete);
		$res = $res->fetchAll(PDO::FETCH_ASSOC);
		
		$comptage = 'SELECT COUNT(DISTINCT tag) as nb_pages '.
				'FROM `eFloreRedaction_pages` '.
				'WHERE '.$condition.' ';
		
		$res_comptage = $this->bdd->query($comptage);
		$res_comptage = $res_comptage->fetch(PDO::FETCH_ASSOC);
		
		return array($res, $res_comptage['nb_pages']);
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
$pages->getPages();
?>