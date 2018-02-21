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
			case 'sentier-informations':
				$this->getInformationsSentier();
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
			case 'sentier-validation':
				$this->ajouterValidationASentier($data);
				break;
			case 'sentier-meta':
				$this->ajouterMetaASentier($data);
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
			case 'sentier-suppression':
				$this->ressusciterSentier($data);
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

		$requete = "SELECT t2.id as id, t2.resource as resource, t2.property as property, t2.value as value "
			. "FROM " . $this->config['bdd']['table_prefixe'] . "_triples t1 "
			. "JOIN " . $this->config['bdd']['table_prefixe'] . "_triples t2 ON t1.resource = t2.resource "
			. "LEFT JOIN " . $this->config['bdd']['table_prefixe'] . "_triples t3 ON t1.resource = t3.resource AND t3.property = " . $this->bdd->quote($this->triple_sentier_date_suppression) . " "
			. "WHERE t1.property = " . $this->bdd->quote($this->triple_sentier) . " ";
		if (! $this->estAdmin()) {
			$requete .= "AND t1.value = " . $this->bdd->quote($this->utilisateur['nomWiki']);
			$requete .= " AND t3.value = '' ";
		} else {
			$requete .= "UNION "
			. "SELECT t2.id as id, t1.resource as resource, t2.property as property, t2.value as value "
			. "FROM " . $this->config['bdd']['table_prefixe'] . "_triples t1 "
			. "JOIN " . $this->config['bdd']['table_prefixe'] . "_triples t2 ON t2.value REGEXP CONCAT('\",\"titre\":\"', t1.resource, '\"}$') AND t2.property = " . $this->bdd->quote($this->triple_evenement_sentier_ajout) . " "
			. "WHERE t1.property = " . $this->bdd->quote($this->triple_sentier);
		}

		$res = $this->bdd->query($requete);
		$res = $res->fetchAll(PDO::FETCH_ASSOC);

		// on retourne une liste et non un objet
		$sentiers = array_values($this->miseEnFormeInfosSentiers($res));

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

	private function getTripleBySentier($triple, $sentier_id) {
		$sql =  'SELECT *'
			. ' FROM ' . $this->config['bdd']['table_prefixe'] . '_triples'
			. ' WHERE property = ' . $this->bdd->quote($triple)
			. ' AND resource = ' . $this->bdd->quote($sentier_id)
		;

		return $this->bdd->query($sql)->fetch(PDO::FETCH_ASSOC);
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

	private function getDessinBySentier($sentier_id) {
		$dessin_sql = 'SELECT *'
			. ' FROM ' . $this->config['bdd']['table_prefixe'] . '_triples'
			. ' WHERE property = ' . $this->bdd->quote($this->triple_sentier_dessin)
			. ' AND resource = ' . $this->bdd->quote($sentier_id)
		;

		$dessin_requete = $this->bdd->query($dessin_sql);
		$dessin = $dessin_requete->fetch(PDO::FETCH_ASSOC);

		return $dessin;
	}

	private function getEtatBySentier($sentier_id) {
		$etat_sql = 'SELECT *'
			. ' FROM ' . $this->config['bdd']['table_prefixe'] . '_triples'
			. ' WHERE property = ' . $this->bdd->quote($this->triple_sentier_etat)
			. ' AND resource = ' . $this->bdd->quote($sentier_id)
		;

		$etat_requete = $this->bdd->query($etat_sql);
		$etat = $etat_requete->fetch(PDO::FETCH_ASSOC);

		return $etat;
	}

	private function getMetaBySentier($sentier_id) {
		$meta_sql = 'SELECT *'
			. ' FROM ' . $this->config['bdd']['table_prefixe'] . '_triples'
			. ' WHERE property = ' . $this->bdd->quote($this->triple_sentier_meta)
			. ' AND resource = ' . $this->bdd->quote($sentier_id)
		;

		$meta_requete = $this->bdd->query($meta_sql);
		$meta = $meta_requete->fetch(PDO::FETCH_ASSOC);

		return $meta;
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

	private function buildJsonInfosSentier($sentier, $meta, $localisation = false) {
		$lnglat = array();
		if ($localisation) {
			$lnglat = array(
				$localisation['sentier']['lng'],
				$localisation['sentier']['lat']
			);
		}

		return array(
			'id' => $sentier['id'],
			'nom' => isset($meta['titre']) ? $meta['titre'] : $sentier['resource'],
			'auteur' => isset($meta['auteur']) ? $meta['auteur'] : $sentier['value'],
			'position' => $lnglat,
			'info' => array(
				'horaires' => [],
				'gestionnaire' => '', // ex: Mairie de Montpellier
				'contact' => '', // ex: 04 67 34 70 00
				'site' => '',
				'logo' => ''
			),
			'photo' => '',
			'date_creation' => $this->getTripleBySentier($this->triple_sentier_date_creation, $sentier['resource'])['value'],
			'date_modification' => $this->getTripleBySentier($this->triple_sentier_date_derniere_modif, $sentier['resource'])['value'],
			'date_suppression' => $this->getTripleBySentier($this->triple_sentier_date_suppression, $sentier['resource'])['value'],
		);
	}

	private function formatSentierDetails($sentier) {
		$fiches = $this->getFichesBySentier($sentier['resource']);

		$raw_localisation = $this->getLocalisationBySentier($sentier['resource']);
		$localisation = json_decode($raw_localisation['value'], true);

		$raw_dessin_sentier = $this->getDessinBySentier($sentier['resource']);
		$dessin_sentier = json_decode($raw_dessin_sentier['value'], true);

		$raw_meta_sentier = $this->getMetaBySentier($sentier['resource']);
		$meta = json_decode($raw_meta_sentier['value'], true);

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

		$sentier_details = $this->buildJsonInfosSentier($sentier, $meta, $localisation);

		if ($localisation) {
			$sentier_details['occurrences'] = array();
			foreach ($localisation['individus'] as $individu_id => $individu) {
				list($referentiel, $numero_taxonomique) = $this->digestIndividuId($individu_id);
				// si pour une raison X on n'a pas de nt, on oublie l'occurrence
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
						'auteur_espece' => $fiche_individu['auteur'] ?? '',
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
			$sentier_details['chemin'] = $dessin_sentier;
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

	private function getPublicInfosSentiers() {
		$infos_sentiers_sql = 'SELECT DISTINCT t1.id as id, t1.resource AS resource, t1.value AS value, t2.value AS localisation, t3.value as meta'
			. ' FROM ' . $this->config['bdd']['table_prefixe'] . '_triples AS t1'
			. ' JOIN ' . $this->config['bdd']['table_prefixe'] . '_triples AS t2 ON t1.resource = t2.resource AND t2.property = ' . $this->bdd->quote($this->triple_sentier_localisation)
			. ' LEFT JOIN ' . $this->config['bdd']['table_prefixe'] . '_triples AS t3 ON t1.resource = t3.resource AND t3.property = ' . $this->bdd->quote($this->triple_sentier_meta)
			. ' WHERE t1.property = ' . $this->bdd->quote($this->triple_sentier)
		;

		$infos_sentiers_requete = $this->bdd->query($infos_sentiers_sql);
		return $infos_sentiers_requete->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Liste des sentiers PUBLICS - exclut les sentiers n'ayant pas de nom OU
	 * zéro occurrence d'espèce OU un chemin de moins de deux points
	 */
	private function publicSentiersListe() {
		$infos_sentiers = $this->getPublicInfosSentiers();

		$liste_sentiers = array();
		foreach ($infos_sentiers as $infos_sentier) {
			// élimination des sentiers non valides (difficile à faire dans le
			// SQL à cause des triplets)
			if ($this->sentierPublicValide($infos_sentier)) {
				// formatage du sentier; devrait correspondre à
				// http://floristic.org/wiki/wakka.php?wiki=FormatDonneesSentiers
				$jsonInfosSentier = $this->buildJsonInfosSentier(
					$infos_sentier,
					json_decode($infos_sentier['meta'], true),
					json_decode($infos_sentier['localisation'], true)
				);

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
		// on vérifie que le sentier est validé pour la publication
		// @todo: optimiser et/ou différencier ces tests
		$raw_etat_sentier = $this->getEtatBySentier($infos_sentier['resource']);
		if (empty($raw_etat_sentier) || $raw_etat_sentier['value'] !== 'Validé') return false;
		// pas de nom, pas de chocolat
		if (empty($infos_sentier['resource'])) return false;
		// pas de localisation, pas de chocolat
		if (empty($infos_sentier['localisation'])) return false;
		// au moins un individu exigé
		$loc = json_decode($infos_sentier['localisation'], true);
		if (empty($loc['individus']) || count($loc['individus']) == 0) return false;
		// au moins deux points pour un chemin exigés
		$raw_dessin_sentier = $this->getDessinBySentier($infos_sentier['resource']);
		$dessin_sentier = json_decode($raw_dessin_sentier['value'], true);
		if (empty($dessin_sentier['coordinates']) || count($dessin_sentier['coordinates']) < 2) return false;

		return true;
	}

	private function getPublicSentiers($requete) {
		if (!empty($requete[1])) {
			return $this->publicSentierDetails($requete[1]);
		} else {
			return $this->publicSentiersListe();
		}
	}

	private function sentierExiste($sentier_titre) {
		$retour = false;

		$requete_existe = 'SELECT COUNT(resource) >= 1 as sentier_existe '.
		'FROM '.$this->config['bdd']['table_prefixe'].'_triples '.
		'WHERE property = "'.$this->triple_sentier.'" '.
		'AND TRIM(resource) = '.$this->bdd->quote(trim($sentier_titre));

		$res_existe = $this->bdd->query($requete_existe);
		$res_existe = $res_existe->fetch(PDO::FETCH_ASSOC);

		return $res_existe['sentier_existe'];
	}

	private function ajouterSentier($data) {

		$retour = false;

		if (empty($data['sentierTitre'])) {
			$this->error('400', 'Le paramètre sentierTitre est obligatoire');
		}

		$sentier_titre = $data['sentierTitre'];
		$utilisateur = $this->utilisateur['nomWiki'];
		$utilisateurCourriel = $this->utilisateur['courriel'];

		$res_existe = $this->sentierExiste($sentier_titre);

		if (!$res_existe) {

			$requete_insertion = 'INSERT INTO '.$this->config['bdd']['table_prefixe'].'_triples '.
				'(resource, property, value) VALUES '.
				' ('.$this->bdd->quote($sentier_titre).','.$this->bdd->quote($this->triple_sentier).', '.$this->bdd->quote($utilisateur).'), '.
				' ('.$this->bdd->quote($sentier_titre).','.$this->bdd->quote($this->triple_sentier_date_creation).', UNIX_TIMESTAMP()), '.
				' ('.$this->bdd->quote($sentier_titre).','.$this->bdd->quote($this->triple_sentier_date_derniere_modif).', UNIX_TIMESTAMP()), '.
				' ('.$this->bdd->quote($sentier_titre).','.$this->bdd->quote($this->triple_sentier_date_suppression).', "") '
			;

			$res_insertion = $this->bdd->exec($requete_insertion);
			$retour = ($res_insertion !== false) ? 'OK' : false;

			if ($retour == 'OK') {
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

	private function renommerSentier($sentier_titre, $nouveau_titre) {
		$retour = false;

		if (empty($sentier_titre) || empty($nouveau_titre)) {
			return $this->error('400', 'Le nom est vide');
		}

		$modification_nom_sentier = 'UPDATE '.$this->config['bdd']['table_prefixe'] . '_triples '.
			'SET resource = ' . $this->bdd->quote($nouveau_titre) . ' ' .
			'WHERE resource = ' . $this->bdd->quote($sentier_titre);

		if ($this->bdd->exec($modification_nom_sentier)) {
			$retour = 'OK';
		}

		return $retour;
	}

	/**
	 * Supprime un sentier (soft delete)
	 */
	private function supprimerSentier($data) {
		$retour = false;
		$estCreateur = false;

		if (empty($data['sentierTitre'])) {
			$this->error('400', 'Le paramètre sentierTitre est obligatoire');
		}

		// si pas admin on vérifie la paternité
		if (false === $this->estAdmin()) {
			$requete_createur = 'SELECT value FROM '.$this->config['bdd']['table_prefixe'].'_triples '.
				'WHERE property = "'.$this->triple_sentier.'" '.
				'AND resource = '.$this->bdd->quote($data['sentierTitre'])
			;

			$createur = $this->bdd->query($requete_createur)->fetch(PDO::FETCH_ASSOC)['value'];

			if (!empty($createur) && ($this->utilisateur['nomWiki'] === $createur)) {
				$estCreateur = true;
			}
		}

		// si admin ou createur on supprime
		if ($this->estAdmin() || $estCreateur) {
			$this->stockerDataTriple($this->triple_sentier_date_suppression, time(), $data['sentierTitre']);

			$retour = $this->renommerSentier($data['sentierTitre'], $data['sentierTitre'] . '_deleted_at_' . date('Ymd-His'));
		}

		header('Content-type: text/plain');
		echo $retour;
	}

	/**
	 * Ressuscite (annule le soft delete d') un sentier
	 */
	private function ressusciterSentier($data) {
		$retour = false;

		if (empty($data['sentierTitre'])) {
			$this->error('400', 'Le paramètre sentierTitre est obligatoire');
		}

		if ($this->estAdmin()) {
			// nettoyage du nom du sentier, on retrouve l'original
			$sentier_titre = $base_sentier_titre = strstr($data['sentierTitre'], '_deleted_at_', true);

			// vérifie si le nom n'a pas été réutilisé depuis la suppression
			$suffixe = 0;
			while ($this->sentierExiste($sentier_titre)) {
				$sentier_titre = $base_sentier_titre . '-' . ++$suffixe;
			}

			$this->renommerSentier($data['sentierTitre'], $sentier_titre);

			// pour finir on vide la date de suppression
			$retour = $this->stockerDataTriple($this->triple_sentier_date_suppression, '', $sentier_titre);
		}


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
			$this->mettreAJourLocalisation($sentier_titre, 'suppressionFiche', $page_tag);
		}

		header('Content-type: text/plain');
		echo $retour;
	}

	private function getInformationsSentier() {
		if (empty($_GET['sentierTitre'])) {
			$this->error('400', 'Le paramètre sentierTitre est obligatoire');
		}

		// @todo : faudrait voir à utiliser un truc genre $this->miseEnFormeInfosSentiers() ici

		$localisation = $this->getTripleBySentier($this->triple_sentier_localisation, $_GET['sentierTitre']);
		$dessin = $this->getTripleBySentier($this->triple_sentier_dessin, $_GET['sentierTitre']);
		$etat = $this->getTripleBySentier($this->triple_sentier_etat, $_GET['sentierTitre']);
		$meta = $this->getTripleBySentier($this->triple_sentier_meta, $_GET['sentierTitre']);
		$date_creation = $this->getTripleBySentier($this->triple_sentier_date_creation, $_GET['sentierTitre']);
		$date_derniere_modif = $this->getTripleBySentier($this->triple_sentier_date_derniere_modif, $_GET['sentierTitre']);
		$date_suppression = $this->getTripleBySentier($this->triple_sentier_date_suppression, $_GET['sentierTitre']);

		$retour = array('nbIndividus' => 0);
		if (count($localisation) > 0) {
			$retour = json_decode($localisation['value'], true);
			$retour['nbIndividus'] = count($retour['individus']);
		}

		header('Content-type: application/json');
		echo json_encode(array(
			'localisation' => $retour,
			'dessin' => json_decode($dessin['value'], true),
			'etat' => $etat['value'],
			'meta' => json_decode($meta['value'], true),
			'date_creation' => $date_creation['value'],
			'date_derniere_modif' => $date_derniere_modif['value'],
			'date_suppression' => $date_suppression['value'],
		));

		exit;
	}

	private function ajouterLocalisationASentier($data) {
		if (empty($data['sentierTitre']) || empty($data['sentierLocalisation'])) {
			$this->error('400', 'Les paramètres sentierTitre et sentierLocalisation sont obligatoires');
		}

		$sentier_titre = $data['sentierTitre'];
		$sentier_localisation = json_encode($data['sentierLocalisation']);
		$sentier_dessin = json_encode($data['sentierDessin']);

		$succes = $this->stockerDataTriple($this->triple_sentier_localisation, $sentier_localisation, $sentier_titre);

		if ($succes) {
			$this->stockerDataTriple($this->triple_sentier_dessin, $sentier_dessin, $sentier_titre);
		}

		header('Content-type: text/plain');
		echo $succes;
	}

	private function ajouterValidationASentier($data) {
		if (empty($data['sentierTitre'])) {
			$this->error('400', 'Le paramètre sentierTitre est obligatoire');
		}

		$sentier_titre = $data['sentierTitre'];
		$sentier_etat = null;

		if (!empty($data['sentierEtat'])) {
			if ($this->estAdmin()) {
				$sentier_etat = $data['sentierEtat'];
			} else {
				$this->error('400', 'Bas les pattes, faut être admin');
			}
		} else {
			$sentier_etat = 'En attente';

			$message = 'Bonjour, ' . "\r\n" .
				'vous recevez ce message car vous êtres administrateur des sentiers SmartFlore. ' . "\r\n" .
				'Un nouveau sentier requiert votre attention : ' . "\r\n" .
				"\r\n" .
				'Nom du sentier: ' . $data['sentierTitre'] . "\r\n" .
				"\r\n" .
				'Rendez-vous sur ' . $this->config['smartflore']['application_saisie_url'];

			$headers = 'Content-Type: text/plain; charset="utf-8" ' .
				'Content-Transfer-Encoding: 8bit' .
				'From: smartflore@tela-botanica.org' .
				'Reply-To: no-reply@example.com' .
				'X-Mailer: PHP/' . phpversion();

			$listeAdmins = explode(',', $this->config['auth']['admins']);
			foreach($listeAdmins as $admin) {
				mail(
					$admin,
					'Demande de validation d\'un sentier',
					$message,
					$headers
				);
			}
		}

		$succes = $this->stockerDataTriple($this->triple_sentier_etat, $sentier_etat, $sentier_titre);

		header('Content-type: text/plain');
		echo $succes;
	}

	private function ajouterMetaASentier($data) {
		if (empty($data['sentierTitre']) || empty($data['sentierMeta'])) {
			$this->error('400', 'Les paramètres sentierTitre et sentierMeta sont obligatoires');
		}

		$sentier_titre = $data['sentierTitre'];
		$sentier_meta = json_encode($data['sentierMeta']);

		$succes = $this->stockerDataTriple($this->triple_sentier_meta, $sentier_meta, $sentier_titre);

		header('Content-type: text/plain');
		echo $succes;
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

	protected function mettreAJourLocalisation($sentier_titre, $action, $ressource) {
		$raw_localisation = $this->getLocalisationBySentier($sentier_titre);
		$localisation = json_decode($raw_localisation['value'], true);

		switch ($action) {
			case 'suppressionFiche':
				$nom_espece_a_supprimer = $ressource;
				// enlève les individus correspondant à la fiche d'espèce supprimée
				$localisation['individus'] = array_filter($localisation['individus'], function($individu) use ($nom_espece_a_supprimer) {
					return 0 !== strpos($individu['ficheTag'], $nom_espece_a_supprimer);
				});

				// Si on a plus d'individus localisés on dépublie
				if (count($localisation['individus']) === 0) {
					$this->stockerDataTriple($this->triple_sentier_etat, '', $sentier_titre);
				}

				break;
			default:
				throw new UnexpectedValueException("$action n'est pas une action supportée");
		}

		$update_localisation_sql = 'UPDATE ' . $this->config['bdd']['table_prefixe'] . '_triples'
			. ' SET value = '.$this->bdd->quote(json_encode($localisation))
			. ' WHERE property = ' . $this->bdd->quote($this->triple_sentier_localisation)
			. ' AND resource = ' . $this->bdd->quote($sentier_titre)
		;

		return $this->bdd->exec($update_localisation_sql);
	}

	/**
	 * Enregistre (créé ou màj) un triple $triple de valeur $data pour le
	 * sentier $sentier_titre
	 * Parfait pour stocker des meta, du json, toussa
	 *
	 * @param      string  $triple         Le triple
	 * @param      string  $data           Les données
	 * @param      string  $sentier_titre  Le titre du sentier
	 */
	protected function stockerDataTriple($triple, $data, $sentier_titre) {
		$requete_existe = 'SELECT COUNT(resource) as triple_existe'
			. ' FROM ' . $this->config['bdd']['table_prefixe'] . '_triples'
			. ' WHERE resource = ' . $this->bdd->quote($sentier_titre)
			. ' AND property = ' . $this->bdd->quote($triple)
		;

		$res_existe = $this->bdd->query($requete_existe);
		$res_existe = $res_existe->fetch(PDO::FETCH_ASSOC);

		if ($res_existe['triple_existe'] == 0) {
			$requete = 'INSERT INTO ' . $this->config['bdd']['table_prefixe'] . '_triples'
				. ' (resource, property, value) VALUES'
				. ' ('
					. $this->bdd->quote($sentier_titre)
					. ',"' . $triple . '"'
					. ', ' . $this->bdd->quote($data)
				. ' )'
			;
		} else {
			$requete = 'UPDATE ' . $this->config['bdd']['table_prefixe'] . '_triples'
				. ' SET value = '.$this->bdd->quote($data)
				. ' WHERE resource = ' . $this->bdd->quote($sentier_titre)
				. ' AND property = ' . $this->bdd->quote($triple)
			;
		}

		$res = $this->bdd->exec($requete);
		$retour = false;
		if ($res !== false) {
			$retour = 'OK';
			$this->mettreAJourDateDerniereModif($sentier_titre);
		}

		return $retour;
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
