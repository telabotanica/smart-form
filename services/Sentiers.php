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
			case 'sentier-localisation':
				$this->getLocalisationASentier();
				break;
			case 'sentiers':
				// Diffère de 'sentier', informations formatées pour l'appli smartflore mobile
				// Pas d'authentification
				$this->getPublicSentiers($requete);
				break;
			default:
				$this->error(400, "Aucune commande connue n'a été spécifiée");
				break;
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
			case 'sentier-localisation':
				$this->ajouterLocalisationASentier($data);
				break;
			default:
				$this->error(400, "Aucune commande connue n'a été spécifiée");
				break;
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
			case 'sentier-localisation':
				$this->supprimerLocalisationASentier($data);
				break;
			default:
				$this->error(400, "Aucune commande connue n'a été spécifiée");
				break;
		}
	}

	/**
	 * Renvoie les sentiers de l'utilisateur en cours seulement. Si celui-ci est
	 * administrateur (liste d'adresses emails dans config.ini), les sentiers de
	 * tous les utilisateurs seront retournés.
	 */
	private function getSentiers() {

		$this->verifierAuthentification();
		$estAdmin = $this->estAdmin();
		$utilisateur = $this->utilisateur['nomWiki'];

		$requete = "SELECT t2.id as id, t2.resource as resource, t2.property as property, t2.value as value "
			. "FROM " . $this->config['bdd']['table_prefixe'] . "_triples t1 "
			. "JOIN " . $this->config['bdd']['table_prefixe'] . "_triples t2 ON t1.resource = t2.resource "
			. "WHERE t1.property = " . $this->bdd->quote($this->triple_sentier) . " ";
		if (! $estAdmin) {
			$requete .= "AND t1.value = " . $this->bdd->quote($utilisateur);
		}

		$res = $this->bdd->query($requete);
		$res = $res->fetchAll(PDO::FETCH_ASSOC);

		$sentiersNommes = array();
		foreach($res as $r) {
			$nomSentier = $r['resource'];
			if (!array_key_exists($nomSentier, $sentiersNommes)) {
				$sentiersNommes[$nomSentier] = array(
					'titre' => $nomSentier,
					'fiches' => array()
				);
			}
			// chargement des propriétés selon le triplet en cours
			switch ($r['property']) {
				case $this->triple_sentier:
					$sentiersNommes[$nomSentier]['auteur'] = $r['value'];
					break;
				case $this->triple_sentier_meta:
					$sentiersNommes[$nomSentier]['meta'] = $r['value'];
					break;
				case $this->triple_sentier_date_creation:
					$sentiersNommes[$nomSentier]['date_creation'] = $r['value'];
					break;
				case $this->triple_sentier_date_derniere_modif:
					$sentiersNommes[$nomSentier]['date_derniere_modif'] = $r['value'];
					break;
			}
		}

		// on retourne une liste et non un objet
		$sentiers = array_values($sentiersNommes);

		$retour = array('pagination' => array('total' => count($sentiers)), 'resultats' => $sentiers);

		header('Content-type: application/json');
		echo json_encode($retour);
		exit;
	}

	private function getSentierById($sentier_id) {
		$sentier_sql = 'SELECT *'
			. ' FROM ' . $this->config['bdd']['table_prefixe'] . '_triples'
			. ' WHERE property = ' . $this->bdd->quote($this->triple_sentier)
			. ' AND resource = ' . $this->bdd->quote($sentier_id)
		;

		$sentier_requete = $this->bdd->query($sentier_sql);
		$sentier = $sentier_requete->fetch(PDO::FETCH_ASSOC);

		return $sentier;
	}

	private function getLocalisationBySentier($sentier_id) {
		$localisation_sql = 'SELECT *'
			. ' FROM ' . $this->config['bdd']['table_prefixe'] . '_triples'
			. ' WHERE property = ' . $this->bdd->quote($this->triple_sentier_localisation)
			. ' AND resource = ' . $this->bdd->quote($sentier_id)
		;

		$localisation_requete = $this->bdd->query($localisation_sql);
		$localisation = $localisation_requete->fetch(PDO::FETCH_ASSOC);

		return $localisation;
	}

	/**
	 * Retourne un tableau des fiches d'un sentier, indexé par le tag de la fiche
	 *
	 * @param      string  $sentier_id  (Titre du sentier)
	 *
	 * @return     array
	 */
	private function getFichesBySentier($sentier_id) {
		$fiches_sql = 'SELECT *'
			. ' FROM ' . $this->config['bdd']['table_prefixe'] . '_triples'
			. ' WHERE property = ' . $this->bdd->quote($this->triple_sentier_fiche)
			. ' AND value = ' . $this->bdd->quote($sentier_id)
		;

		$fiches = array();
		$fiches_requete = $this->bdd->query($fiches_sql);
		while ($fiche = $fiches_requete->fetch(PDO::FETCH_ASSOC)) {
			$fiches[$fiche['resource']] = $fiche;
		}

		return $fiches;
	}

	private function buildJsonInfosSentier($sentier, $localisation = false) {
		$lnglat = array();
		if ($localisation) {
			$lnglat = array(
				$localisation['sentier']['lng'],
				$localisation['sentier']['lat']
			);
		}

		return array(
			'id' => $sentier['id'],
			'nom' => $sentier['resource'],
			'auteur' => $sentier['value'],
			'position' => $lnglat,
			'info' => array(
				'horaires' => [],
				'gestionnaire' => '', // ex: Mairie de Montpellier
				'contact' => '', // ex: 04 67 34 70 00
				'site' => '',
				'logo' => ''
			),
			'photo' => '',
			'date_creation' => '',
			'date_modification' => ''
		);
	}

	private function formatSentierDetails($sentier) {
		$fiches = $this->getFichesBySentier($sentier['resource']);

		$raw_localisation = $this->getLocalisationBySentier($sentier['resource']);
		$localisation = json_decode($raw_localisation['value'], true);

		// On va chercher sur eflore les infos complètes de chaque fiche
		$fiches_eflore = array();
		foreach ($fiches as $fiche) {
			list($referentiel, $numero_taxonomique) = $this->splitNt($fiche['resource']);

			// construction de l'url
			$fiche_eflore_url_template = $this->config['eflore']['url_base'] . $this->config['eflore']['infos_taxons_export_url'];
			$fiche_eflore_url = sprintf($fiche_eflore_url_template, strtolower($referentiel), 'nt:' . $numero_taxonomique);
			// recuperation des infos
			$fiche_eflore = json_decode(@file_get_contents($fiche_eflore_url), true);

			$fiches_eflore[$fiche['resource']] = $fiche_eflore;
		}

		$sentier_details = $this->buildJsonInfosSentier($sentier, $localisation);

		if ($localisation) {
			$sentier_details['occurrences'] = array();
			foreach ($localisation['individus'] as $individu_id => $individu) {
				list($referentiel, $numero_taxonomique) = $this->digestIndividuId($individu_id);
				// si pour une raison X on n'a pas de nn, on oublie l'occurrence
				if (empty($numero_taxonomique)) {
					continue;
				}
				$fiche_individu = $fiches_eflore[$this->formaterPageNom($referentiel, $numero_taxonomique)];
				$fiche_url = sprintf($this->config['eflore']['fiche_mobile'], $referentiel, $fiche_individu['nom_retenu.id']);

				$sentier_details['occurrences'][] = array(
					'position' => array(
						$individu['lng'],
						$individu['lat']
					),
					'taxo' => array(
						'espece' => $fiche_individu['nom_sci'],
						'auteur_espece' => $fiche_individu['auteur'],
						'auteur_genre' => '',
						'auteur_famille' => '',
						'genre' => $fiche_individu['genre'],
						'famille' => $fiche_individu['famille'],
						'referentiel' => $referentiel,
						'num_nom' => $fiche_individu['nom_retenu.id']
					),
					'fiche' => array(
						'fr' => $fiche_url
					),
					'infos' => array(
						'photo' => ''
					)
				);
			}

			// génération d'un chemin minimaliste pour remplir les critères
			// d'admissibilité @TODO remplacer par les vrais chemins
			$sentier_details['chemin'] = array(
				'type' => 'LineString',
				'coordinates' => array(
					array($localisation['sentier']['lng'], $localisation['sentier']['lat']),
					array($individu['lng'], $individu['lat'])
				)
			);
		}

		return json_encode($sentier_details);
	}

	/**
	 * Retourne les détails d'un sentier en fontion de son "identifiant", qui
	 * est en fait son nom ! (et pas l'ID interne de la BDD)
	 */
	private function publicSentierDetails($sentier_id) {
		$sentier_id = urldecode($sentier_id);
		$sentier = $this->getSentierById($sentier_id);

		if ($sentier) {
			header('Content-type: application/json');
			echo $this->formatSentierDetails($sentier);
		} else {
			header('Content-type: text/plain');
			return $this->error('400', 'Ce sentier n=\'existe pas');;
		}
	}

	/**
	 * Liste des sentiers PUBLICS - exclut les sentiers n'ayant pas de nom OU
	 * zéro occurrence d'espèce OU un chemin de moins de deux points
	 */
	private function publicSentiersListe() {
		$liste_sentiers = array();

		$infos_sentiers_sql = 'SELECT DISTINCT t1.id as id, t1.resource AS resource, t1.value AS value, t2.value AS localisation'
			. ' FROM ' . $this->config['bdd']['table_prefixe'] . '_triples AS t1'
			. ' JOIN ' . $this->config['bdd']['table_prefixe'] . '_triples AS t2 ON t1.resource = t2.resource'
			. ' WHERE t1.property = ' . $this->bdd->quote($this->triple_sentier)
			. ' AND t2.property = ' . $this->bdd->quote($this->triple_sentier_localisation)
		;

		$infos_sentiers_requete = $this->bdd->query($infos_sentiers_sql);
		$infos_sentiers = $infos_sentiers_requete->fetchAll(PDO::FETCH_ASSOC);

		foreach ($infos_sentiers as $infos_sentier) {
			// élimination des sentiers non valides (difficile à faire dans le
			// SQL à cause des triplets)
			if ($this->sentierPublicValide($infos_sentier)) {
				// formatage du sentier; devrait correspondre à
				// http://floristic.org/wiki/wakka.php?wiki=FormatDonneesSentiers
				$jsonInfosSentier = $this->buildJsonInfosSentier($infos_sentier, json_decode($infos_sentier['localisation'], true));
				$ressourceUrlEncodee = urlencode($infos_sentier['resource']);
				$jsonInfosSentier['details'] = sprintf($this->config['service']['details_sentier_url'], $ressourceUrlEncodee);
				$liste_sentiers[] = $jsonInfosSentier;
			}
		}

		header('Content-type: application/json');
		echo json_encode($liste_sentiers);
	}

	/**
	 * Retourne true si un sentier est considéré comme diffusable : un ID, un
	 * nom, au moins une occurrence d'espèce; false sinon. @TODO intégrer la
	 * notion de validation par l'auteur + par l'administrateur
	 */
	protected function sentierPublicValide($infos_sentier) {
		// pas de nom, pas de chocolat
		if (empty($infos_sentier['resource'])) return false;
		// pas de localisation, pas de chocolat
		if (empty($infos_sentier['localisation'])) return false;
		// au moins un individu exigé
		$loc = json_decode($infos_sentier['localisation'], true);
		if (empty($loc['individus']) || count($loc['individus']) == 0) return false;
		// @TODO gérer la présence d'un vrai chemin :
		// if (empty($loc['chemin']) || count($loc['chemin']) < 2) return false;

		return true;
	}

	private function getPublicSentiers($requete) {
		if (!empty($requete[1])) {
			return $this->publicSentierDetails($requete[1]);
		} else {
			return $this->publicSentiersListe();
		}
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
					' ('.$this->bdd->quote($sentier_titre).',"'.$this->triple_sentier.'", '.$this->bdd->quote($utilisateur).'), '.
					' ('.$this->bdd->quote($sentier_titre).',"'.$this->triple_sentier_date_creation.'", UNIX_TIMESTAMP()), '.
					' ('.$this->bdd->quote($sentier_titre).',"'.$this->triple_sentier_date_derniere_modif.'", UNIX_TIMESTAMP()) ';

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

	/**
	 * Supprime un sentier et tout ce qui le concerne : dates, métadonnées,
	 * localisation, fiches liées
	 */
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
		
		// Supprimer également les dates, les métadonnées et la localisation
		// @WARNING attention c'est violent @TODO vérifier que ça pète rien
		$requete_suppression_proprietes = 'DELETE FROM '.$this->config['bdd']['table_prefixe'].'_triples '.
				'WHERE resource = '.$this->bdd->quote($sentier_titre).' '.
				'AND property IN ('
				.$this->bdd->quote($this->triple_sentier_date_creation).','
				.$this->bdd->quote($this->triple_sentier_date_derniere_modif).','
				.$this->bdd->quote($this->triple_sentier_localisation).','
				.$this->bdd->quote($this->triple_sentier_meta).')'
				;
		$res_suppression_proprietes = $this->bdd->exec($requete_suppression_proprietes);

		$retour = ($res_suppression !== false)
				&& ($res_suppression_fiches !== false)
				&& ($res_suppression_proprietes !== false)
					? 'OK' : false;

		header('Content-type: text/plain');
		echo $retour;
	}

	private function getFichesASentier() {

		if(empty($_GET['sentierTitre'])) {
			$this->error('400', 'Le paramètre sentierTitre est obligatoire');
		}

		$res = $this->getFichesBySentier($_GET['sentierTitre']);

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
					' ('.$this->bdd->quote($page_tag).',"'.$this->triple_sentier_fiche.'", '.$this->bdd->quote($sentier_titre).')';

			$res_insertion = $this->bdd->exec($requete_insertion);
			$retour = false;
			if ($res_insertion !== false) {
				$retour = 'OK';
				$this->mettreAJourDateDerniereModif($sentier_titre);
			}

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
		$retour = false;
		if ($res_suppression !== false) {
			$retour = 'OK';
			$this->mettreAJourDateDerniereModif($sentier_titre);
		}

		header('Content-type: text/plain');
		echo $retour;
	}

	private function getLocalisationASentier() {
		if (empty($_GET['sentierTitre'])) {
			$this->error('400', 'Le paramètre sentierTitre est obligatoire');
		}

		$localisation = $this->getLocalisationBySentier($_GET['sentierTitre']);

		$retour = array('nbIndividus' => 0);
		if (count($localisation) > 0) {
			$retour = json_decode($localisation['value'], true);
			$retour['nbIndividus'] = count($retour['individus']);
		}

		header('Content-type: application/json');
		echo json_encode(array(
			'localisation' => $retour
		));

		exit;
	}

	private function ajouterLocalisationASentier($data) {
		if (empty($data['sentierTitre']) || empty($data['sentierLocalisation'])) {
			$this->error('400', 'Les paramètres sentierTitre et sentierLocalisation sont obligatoires');
		}

		$sentier_titre = $data['sentierTitre'];
		$sentier_localisation = json_encode($data['sentierLocalisation']);

		$requete_existe = 'SELECT COUNT(resource) as localisation_sentier_existe'
			. ' FROM ' . $this->config['bdd']['table_prefixe'] . '_triples'
			. ' WHERE resource = ' . $this->bdd->quote($sentier_titre)
			. ' AND property = ' . $this->bdd->quote($this->triple_sentier_localisation)
		;

		$res_existe = $this->bdd->query($requete_existe);
		$res_existe = $res_existe->fetch(PDO::FETCH_ASSOC);

		if ($res_existe['localisation_sentier_existe'] == 0) {
			$requete = 'INSERT INTO ' . $this->config['bdd']['table_prefixe'] . '_triples'
				. ' (resource, property, value) VALUES'
				. ' ('
					. $this->bdd->quote($sentier_titre)
					. ',"' . $this->triple_sentier_localisation . '"'
					. ', ' . $this->bdd->quote($sentier_localisation)
				. ' )'
			;
		} else {
			$requete = 'UPDATE ' . $this->config['bdd']['table_prefixe'] . '_triples'
				. ' SET value = '.$this->bdd->quote($sentier_localisation)
				. ' WHERE resource = ' . $this->bdd->quote($sentier_titre)
				. ' AND property = ' . $this->bdd->quote($this->triple_sentier_localisation)
			;
		}

		$res = $this->bdd->exec($requete);
		$retour = false;
		if ($res !== false) {
			$retour = 'OK';
			$this->mettreAJourDateDerniereModif($sentier_titre);
		}

		header('Content-type: text/plain');
		echo $retour;
	}

	/**
	 * Met à jour la date de dernière modification d'un sentier
	 * @WARNING considère que ce triplet existe (le ON DUPLICATE KEY UPDATE 
	 * ne marche pas avec un id auto_increment)
	 */
	protected function mettreAJourDateDerniereModif($sentier_titre) {
		$reqMajDdm = 'UPDATE '.$this->config['bdd']['table_prefixe'] . '_triples '.
			'SET value = UNIX_TIMESTAMP() '.
			'WHERE resource = '.$this->bdd->quote($sentier_titre).' '.
			'AND property = '.$this->bdd->quote($this->triple_sentier_date_derniere_modif);

		return $this->bdd->exec($reqMajDdm);
	}

	private function supprimerLocalisationASentier($data) {
		$retour = false;

		if (empty($data['sentierTitre'])) {
			$this->error('400', 'Le paramètre sentierTitre est obligatoire');
		}

		$requete_suppression = 'DELETE FROM ' . $this->config['bdd']['table_prefixe'] . '_triples'
			. ' WHERE resource = ' . $this->bdd->quote($data['sentierTitre'])
			. ' AND property = ' . $this->bdd->quote($this->triple_sentier_localisation)
		;

		$res_suppression = $this->bdd->exec($requete_suppression);
		$retour = false;
		if ($res_suppression !== false) {
			$retour = 'OK';
			$this->mettreAJourDateDerniereModif($data['sentierTitre']);
		}

		header('Content-type: text/plain');
		echo $retour;
	}
}

$sentiers = new Sentiers();
?>
