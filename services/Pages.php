<?php
require_once('SmartFloreService.php');
// declare(encoding='UTF-8');
/**
 * Web service de consultation des pages smart'Flore
 *
 * @category	php 5.2
 * @package		smart-form
 * @author		Aurélien Peronnet < aurelien@tela-botanica.org>
 * @copyright	Copyright (c) 2015, Tela Botanica (accueil@tela-botanica.org)
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
		$recherche['referentiel_verna'] = !empty($_GET['referentiel_verna']) && $_GET['referentiel_verna'] != "null" ? $_GET['referentiel_verna'] : null;

		$recherche['num_tax'] = !empty($_GET['num_tax']) ? $_GET['num_tax'] : '%';

		$recherche['recherche'] = !empty($_GET['recherche']) ? $_GET['recherche'] : '%';

		$recherche['pages_existantes'] = isset($_GET['pages_existantes']) ? $_GET['pages_existantes'] == 'true' : false;
		$recherche['nom_verna'] = isset($_GET['nom_verna']) ? $_GET['nom_verna'] == 'true' : false;

		$recherche['retour'] = isset($_GET['retour']) ? $_GET['retour'] : 'max';

		if($recherche['retour'] == 'un') {
			// Recherche d'une page, pour édition directe à partir d'un lien
			$retour = $this->getPage($recherche);
		} elseif($recherche['retour'] == 'min') {
			// Recherche et renvoie uniquement les noms
			// pour assurer une autocomplétion réactive
			$retour = $this->getPagesPourRechercheAsync($recherche);
		} else {
			// Recherche normale, avec renvoi d'infos complètes
			$retour = $this->getPagesPourRechercheNormale($recherche);
		}

		header('Content-type: application/json');
		echo json_encode($retour);
		exit;
	}

	function getPage($recherche) {

		$retour = array('pagination' => array('total' => 0), 'resultats' => array());

		$referentiel = $recherche['referentiel'];
		$num_tax = $recherche['num_tax'];

		$url_eflore_tpl = $this->config['eflore']['url_base'] . $this->config['eflore']['infos_taxons_url'];
		$url = sprintf($url_eflore_tpl, strtolower($referentiel), $num_tax);
		$infos = @file_get_contents($url);
		$infos = json_decode($infos, true);

		if(!empty($infos['entete']) && $infos['entete']['total'] > 0) {

			$num_nom = array_pop(array_keys($infos['resultat']));
			$nom = array_pop($infos['resultat']);

			$recherche['noms_pages'][] = '"'.$this->formaterPageNom($referentiel, $num_tax).'"';
			$recherche['debut'] = 0;
			$recherche['limite'] = 1;
			list($pages_wiki, $nb_pages_wiki) = $this->getPagesWikiParRechercheExacte($recherche);

			$retour['resultats'][] = array(
				'existe' => $nb_pages_wiki > 0,
				'favoris' => false, // pas important ici
				'tag' => $recherche['noms_pages'][0],
				'time' => '',
				'owner' => '',
				'user' => '',
				'nb_revisions' => 0,
				'infos_taxon' => array(
						"num_taxonomique" => $recherche['num_tax'],
						"nom_sci"=> $nom['nom_sci'],
						"nom_sci_complet" => $nom['nom_sci_complet'],
						"retenu" => $nom['retenu'],
						"num_nom" => $num_nom,
						"referentiel" => $recherche['referentiel'],
						"noms_vernaculaires" => array() // pas important ici
				)
			);
			$retour['resultats'][0] = array_merge($retour['resultats'][0], $pages_wiki[0]);
			$retour['pagination']['total'] = 1;
		}

		return $retour;

	}

	function getPagesPourRechercheNormale($recherche) {
		if($recherche['pages_existantes']) {
			$retour = $this->getPagesExistantes($recherche);
		} else {
			if($recherche['nom_verna']) {
				$retour = $this->getPagesToutesParRechercheVernaculaire($recherche);
			} else {
				$retour = $this->getPagesToutesParRechercheScientifique($recherche);
			}
		}

		// Si l'utilisateur est connecté, on recherche également quelles sont les pages
		// présentes dans les résultats qui sont dans ses favoris
		if(!empty($this->utilisateur['courriel']) && !empty($retour['resultats'])) {
			$utilisateur = $this->utilisateur['courriel'];
			$retour = $this->joindreFavoris($utilisateur, $retour);
		}
		// $retour['fiches_a_num_nom'] était là pour des raisons pratique de
		// tri et d'accès, on le supprime avant de renvoyer les résultats
		unset($retour['fiches_a_num_nom']);
		// $retour['resultats'] est indexé par referentiel.num_nom pour des raisons pratique de
		// tri et d'accès, on désindexe avant de renvoyer les résultats (l'ordre est conservé)
		$retour['resultats'] = array_values($retour['resultats']);

		return $retour;
	}

	function getPagesExistantes($recherche) {

		$recherche['noms_pages'] = 'SmartFlore'.$recherche['referentiel'].'nt'.$recherche['num_tax'];
		list($pages_wiki, $nb_pages) = $this->getPagesWikiParRechercheFloue($recherche);

		$retour = array('pagination' => array('total' => $nb_pages), 'resultats' => array());
		$retour = array_merge($retour, $this->completerPagesParInfosTaxon($pages_wiki));

		return $retour;
	}

	function getPagesToutesParRechercheScientifique($recherche) {

		$retour = array('pagination' => array('total' => 0), 'resultats' => array(), 'fiches_a_num_nom' => array());

		$infos = $this->consulterRechercheNomsSciEflore($recherche);

		if(!empty($infos['entete']) && $infos['entete']['total'] > 0) {

			$retour['pagination']['total'] = $infos['entete']['total'];
			$noms_pages = array();
			$num_tax_a_nums_noms = array();

			foreach($infos['resultat'] as &$nom) {
				if(isset($nom['num_taxonomique'])) {
					$num_nom = $nom['id'];
					$num_tax_a_nums_noms[$nom['num_taxonomique']][] = $num_nom;

					$nom_page = $this->formaterPageNom($recherche['referentiel'], $nom['num_taxonomique']);
					$retour['fiches_a_num_nom'][$nom_page] = $num_nom;
					// le faire maintenant nous fait économiser un array_map plus tard
					$noms_pages[] = $this->bdd->quote($nom_page);
					// On indexe par num nom pour avoir une ligne par nom et pas par taxon
					$retour['resultats'][$recherche['referentiel'].$num_nom] = array(
						'existe' => false,
						'favoris' => false,
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
								"referentiel" => $recherche['referentiel'],
								"noms_vernaculaires" => array()
						)
					);
				}
			}

			if($recherche['referentiel_verna'] != null) {
				// retour est modifié par référence dans la fonction compléter par noms vernaculaires
				// TODO: faire ceci aussi souvent que possible !
				$this->completerPagesParNomsVernaculaires($recherche['referentiel'], $recherche['referentiel_verna'], $num_tax_a_nums_noms, $retour);
			}


			// Ajout des informations des pages du wiki si elles existent
			$recherche['noms_pages'] = $noms_pages;
			$recherche['debut'] = 0;
			list($pages_wiki, $nb_pages_wiki) = $this->getPagesWikiParRechercheExacte($recherche);

			foreach($pages_wiki as $page_wiki) {
				list($referentiel, $nt) = $this->splitNt($page_wiki['tag']);
				foreach($num_tax_a_nums_noms[$nt] as $num_nom_fiche) {
					$retour['resultats'][$referentiel.$num_nom_fiche] = array_merge($retour['resultats'][$referentiel.$num_nom_fiche], $page_wiki);
					$retour['resultats'][$referentiel.$num_nom_fiche]['existe'] = true;
				}
			}
		}

		return $retour;
	}

	function getPagesToutesParRechercheVernaculaire($recherche) {

		$retour = array('pagination' => array('total' => 0), 'resultats' => array(), 'fiches_a_num_nom' => array());

		$infos_verna = $this->consulterRechercheNomsVernaEflore($recherche);

		if(!empty($infos_verna['entete']) && $infos_verna['entete']['total'] > 0) {

			$retour['pagination']['total'] = $infos_verna['entete']['total'];
			$noms_pages = array();
			$nts_a_num_noms_verna = array();

			foreach($infos_verna['resultat'] as $num_nom_verna => &$nom_verna) {
				// Des fois eflore est très con et dans un référentiel num_taxon est dans "numero_taxonomique"
				// et des fois dans "num_taxon", CECI DOIT CESSER !!!
				$num_taxon_nom_verna_virg = isset($nom_verna['num_taxon']) ? $nom_verna['num_taxon'] : $nom_verna['num_tax'];

				$tableau_nums_taxons_nom_verna = explode(',', $num_taxon_nom_verna_virg);

				foreach($tableau_nums_taxons_nom_verna as $num_taxon_nom_verna) {
					$nom_page = $this->formaterPageNom($recherche['referentiel'], $num_taxon_nom_verna);
					// le faire maintenant nous fait économiser un array_map plus tard
					$noms_pages[] = $this->bdd->quote($nom_page);
					// Pour ne pas perdre de ligne on indexe par référentiel et num taxonomique
					// (car un nom verna peut correspondre à plusieurs taxons et vice versa)
					if(!isset($retour['resultats'][$recherche['referentiel'].$num_taxon_nom_verna])) {
						$retour['resultats'][$recherche['referentiel'].$num_taxon_nom_verna] = array(
								'existe' => false,
								'favoris' => false,
								'tag' => $nom_page,
								'time' => '',
								'owner' => '',
								'user' => '',
								'nb_revisions' => 0,
								'infos_taxon' => array(
										"num_taxonomique" => $num_taxon_nom_verna,
										"nom_sci"=> '',
										"nom_sci_complet" => '',
										"retenu" => '',
										"num_nom" => '',
										"referentiel" => $recherche['referentiel'],
										"noms_vernaculaires" => array($nom_verna['nom'])
								)
						);
					} else {
						$retour['resultats'][$recherche['referentiel'].$num_taxon_nom_verna]['infos_taxon']['noms_vernaculaires'][] = $nom_verna['nom'];
					}

					$nts_a_num_noms_verna[$num_taxon_nom_verna][] = $num_nom_verna;
				}
			}

			// Ajout des informations des pages du wiki si elles existent
			$recherche['noms_pages'] = $noms_pages;
			list($pages_wiki, $nb_pages_wiki) = $this->getPagesWikiParRechercheExacte($recherche);

			foreach($pages_wiki as $page_wiki) {
				list($referentiel, $nt) = $this->splitNt($page_wiki['tag']);
				$infos_page_nom = $retour['resultats'][$referentiel.$nt];
				$retour['resultats'][$recherche['referentiel'].$nt] = array_merge($infos_page_nom, $page_wiki);
			}

			// Ajout des informations des noms scientifiques
			$url_eflore_sci_tpl = $this->config['eflore']['url_base'] . $this->config['eflore']['infos_taxons_url'];
			$url_sci = sprintf($url_eflore_sci_tpl, strtolower($recherche['referentiel']), implode(',', array_keys($nts_a_num_noms_verna)));

			$infos_noms_sci = file_get_contents($url_sci);
			$infos_noms_sci = json_decode($infos_noms_sci, true);

			if(!empty($infos_noms_sci['entete'])) {
				foreach($infos_noms_sci['resultat'] as $num_nom => $infos_a_nt) {
					$retour['fiches_a_num_nom'][$this->formaterPageNom($recherche['referentiel'], $infos_a_nt['num_taxonomique'])] = $num_nom;
					$infos_taxon_t = $retour['resultats'][$recherche['referentiel'].$infos_a_nt['num_taxonomique']]['infos_taxon'];
					$infos_taxon_t['num_nom'] = $num_nom;
					$retour['resultats'][$recherche['referentiel'].$infos_a_nt['num_taxonomique']]['infos_taxon'] = array_merge($infos_taxon_t, $infos_a_nt);
				}
			}
		}

		return $retour;
	}

	function getPagesPourRechercheAsync($recherche) {
		$retour = array('pagination' => array('total' => 0), 'resultats' => array());
		$infos = array();
		$case_nom = '';

		if($recherche['nom_verna']) {
			$case_nom = 'nom';
			$infos = $this->consulterRechercheNomsVernaEflore($recherche);
		} else {
			$case_nom = 'nom_sci';
			$infos = $this->consulterRechercheNomsSciEflore($recherche);

		}

		if(!empty($infos['entete']) && $infos['entete']['total'] != 0) {
			$retour['pagination'] = $infos['entete']['total'];
			foreach ($infos['resultat'] as $nom) {
				$retour['resultats'][] = $nom[$case_nom];
			}
		}

		return $retour;
	}

	function joindreFavoris($utilisateur, $retour) {
		$favoris = $this->getFavorisPourUtilisateur($utilisateur, array_keys($retour['fiches_a_num_nom']));
		foreach($favoris as $pages_favorite) {
			list($referentiel, $nt) = $this->splitNt($pages_favorite['resource']);
			$num_nom = $retour['fiches_a_num_nom'][$pages_favorite['resource']];
			$retour['resultats'][strtoupper($referentiel).$num_nom]['favoris'] = true;
		}
		return $retour;
	}
}

$pages = new Pages();
?>
