<?php
require_once('SmartFloreService.php');

/**
 * Web service d'export des pages et sentiers smart'Flore
 *
 * @category	php 5.2
 * @package		smart-form
 * @author		Aurélien Peronnet < aurelien@tela-botanica.org>
 * @copyright	Copyright (c) 2015, Tela Botanica (accueil@tela-botanica.org)
 * @license		http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license		http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 */
class Export extends SmartFloreService {

	protected $cheminExportTmp = null;
	protected $apiExportPdf = null;

	function get($requete) {

		switch($requete[0]) {
			case 'fiche':
				if(!empty($_GET['referentiel']) && !empty($_GET['num_tax'])) {
					// Export d'une seule fiche (éventuellement accompagnée d'un nom de sentier à intégrer dans le titre)
					$sentier = !empty($_GET['sentierTitre']) ? $_GET['sentierTitre'] : '';
					$this->getExportFiche($_GET['referentiel'], $_GET['num_tax'], $sentier);
				} else {
					$this->error(400, "Vous devez spécifier un référentiel avec un numéro taxonomique");
				}
				break;
			case 'sentier':
				if(!empty($_GET['sentierTitre'])) {
					// export de toutes les fiches d'un sentier
					$this->getExportSentier($_GET['sentierTitre']);
					break;
				} else {
					$this->error(400, "Vous devez spécifier un nom de sentier et une action");
				}
				break;
			case 'sentiersCSV':
				$this->getExportSentiersCSV();
				break;
			default:
				$this->error(400, "Aucune commande n'a été spécifiée");
		}
	}

	protected function remplacerCheminParUrl($chemin) {
		$chemin = realpath($chemin);
		return 'http://'.str_replace(realpath($_SERVER['DOCUMENT_ROOT']), $_SERVER['HTTP_HOST'], $chemin);
	}

	protected function sluggifierSimple($chaine) {
		return str_replace(array(' ','.'), array('_', ''), $chaine);
	}

	protected function sluggifierPlus($chaine) {
		return $this->sluggifierSimple(preg_replace("/[^A-Za-z0-9 ]/", '', $chaine));
	}

	// http://stackoverflow.com/questions/1364933/htmlentities-in-php-but-preserving-html-tags
	protected function convertirEnEntitesHtmlSaufTags($chaine) {
		$list = get_html_translation_table(HTML_ENTITIES);
		unset($list['"']);
		unset($list['<']);
		unset($list['>']);
		unset($list['&']);

		$search = array_keys($list);
		$values = array_values($list);

		return str_replace($search, $values, $chaine);
	}

	/**
	 * Retourne les informations d'un taxon / d'une fiche
	 */
	protected function informationsTaxonFiche($referentiel, $num_tax, $sentier_titre) {
		// infos sur la fiche et le taxon
		$referentiel = strtolower($referentiel);
		$infos_fiche = $this->chargerInformationsFiche($referentiel, $num_tax);
		$infos_fiche['nom_sentier'] = $this->convertirEnEntitesHtmlSaufTags(ucfirst($sentier_titre));
		$infos_fiche['titre'] = $this->convertirEnEntitesHtmlSaufTags($sentier_titre);
		$infos_fiche['base_style_url'] = $this->remplacerCheminParUrl(dirname(__FILE__).DIRECTORY_SEPARATOR.'squelettes').DIRECTORY_SEPARATOR;
		$infos_fiche['referentiel'] = $referentiel;

		return $infos_fiche;
	}

	/**
	 * Genère le fichier HTML du panneau, puis le convertit en PDF en appelantle service de conversion distant
	 */
	function getExportFiche($referentiel, $num_tax, $sentier_titre = '') {
		// infos taxon
		$infos_fiche = $this->informationsTaxonFiche($referentiel, $num_tax, $sentier_titre);

		// génération du panneau
		$panneau_html = $this->remplirSquelette('panneau', $infos_fiche);

		$nom_fichier = 'panneau-smartflore-'.$this->sluggifierPlus($sentier_titre).'-'.$referentiel.'-'.$infos_fiche['num_nom'];
		// Attention le chemin d'export temporaire doit se trouver au dessous du
		// dossier Web du serveur afin d'être accessible par une URL
		$chemin_html = $this->config['export']['chemin_export_tmp'].$nom_fichier.'.html';
		// sauvegarde dans un fichier qui sera accessible directement pour le script de conversion par son url
		file_put_contents($chemin_html, $panneau_html);

		// à décommenter pour afficher la fiche en html
		//echo file_get_contents($chemin_html);exit;

		header("Content-type:application/pdf; charset=utf-8");
		// TODO: envoyer la taille dans le header parce que c'est mieux !
		// Supprimer les espaces et les points permet d'avoir un nom de fichier pas trop dégeulasse lors du téléchargement par le navigateur
		header("Content-Disposition:attachment;filename=".$this->sluggifierSimple($infos_fiche['nom_sci']).".pdf");

		// conversion en PDF
		$url_export_tmp = urlencode($this->remplacerCheminParUrl($chemin_html));
		// Impossible d'installer phantomJs sur sequoia alors on appelle un web service de conversion sur agathis (les chiffres correspond à une taille de format A5)
		echo file_get_contents(sprintf($this->config['export']['pdf_export_url'], $url_export_tmp, 1748, 2481));
		exit;
	}

	/**
	 * Exporte toutes les fiches d'un sentier dans un même fichier PDF
	 */
	protected function getExportSentier($sentier_titre) {
		// TODO: suivant le nombre de fiches, faire un export en plusieurs fois à travers
		// plusieurs appels ajax ou bien en une fois dans la fonction ci dessous
		$this->deciderActionExportSentier($sentier_titre);
	}

	protected function getExportSentiersCSV() {
		$this->verifierAuthentification();

		if (! $this->estAdmin()) {
			exit('faut être admin');
		}

		$sentiers_sql = "SELECT t2.id as id, t2.resource as resource, t2.property as property, t2.value as value "
			. "FROM " . $this->config['bdd']['table_prefixe'] . "_triples t1 "
			. "JOIN " . $this->config['bdd']['table_prefixe'] . "_triples t2 ON t1.resource = t2.resource "
			. "WHERE t1.property = " . $this->bdd->quote($this->triple_sentier) . " "
			. "UNION "
			. "SELECT t2.id as id, t1.resource as resource, t2.property as property, t2.value as value "
			. "FROM " . $this->config['bdd']['table_prefixe'] . "_triples t1 "
			. "JOIN " . $this->config['bdd']['table_prefixe'] . "_triples t2 ON t2.value REGEXP CONCAT('\",\"titre\":\"', t1.resource, '\"}$') AND t2.property = " . $this->bdd->quote($this->triple_evenement_sentier_ajout) . " "
			. "WHERE t1.property = " . $this->bdd->quote($this->triple_sentier) . ";"
		;

		$sentiers_requete = $this->bdd->query($sentiers_sql);
		$sentiers = $sentiers_requete->fetchAll(PDO::FETCH_ASSOC);

		$sentiers = $this->miseEnFormeInfosSentiers($sentiers);

		// collection des entetes pour générer ensuite le csv
		$entetes = array('id'); // pour faire apparaitre id en premier
		foreach ($sentiers as $sentier) {
			$entetes = array_merge($entetes, array_keys($sentier));
		}
		$entetes = array_diff(array_unique($entetes), array('fiches')); // on retire les doublons et 'fiches' qui est un tableau
		//@todo retirer les tableaux proprement (ou convertir en json)

		date_default_timezone_set('Europe/Paris'); // Pour que les date() plus bas ne lèvent pas de warnings
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="export_sentiers_' . date('YmdHis') . '.csv"');
		header('Filename: export_sentiers_' . date('YmdHis') . '.csv');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		// header('Content-Length: ' . filesize($file));

		$sortie_directe = fopen('php://output', 'w');

		// les entetes en première ligne du csv, puis génération des autres lignes
		fputcsv($sortie_directe, $entetes);
		foreach ($sentiers as $sentier) {
			$ligne = array();

			foreach ($entetes as $entete) {
				if (array_key_exists($entete, $sentier)) {
					switch ($entete) {
						case 'meta':
							$ligne[$entete] = urldecode(http_build_query(json_decode($sentier[$entete]),'',', ')); // @todo: transformer ce json avec caractères utf8 échappés proprement
							break;
						case 'dateCreation':
						case 'dateDerniereModif':
						case 'dateSuppression':
							if (!empty($sentier[$entete])) {
								$ligne[$entete] = date('d-m-Y H:i:s', $sentier[$entete]);
							}
							break;
						default:
							$ligne[$entete] = $sentier[$entete];
							break;
					}
				} else {
					$ligne[$entete] = null;
				}
			}

			fputcsv($sortie_directe, $ligne);
		}

		fclose($sortie_directe);
	}

	protected function enregistrerFichePourExportSentier($sentier_titre, $referentiel, $num_tax) {
		// infos taxon
		$infos_fiche = $this->informationsTaxonFiche($referentiel, $num_tax, $sentier_titre);

		// génération du panneau
		$panneau_html = $this->remplirSquelette('panneau', $infos_fiche);

		$nom_fichier = 'panneau-smartflore-'.$referentiel.'-'.$infos_fiche['num_nom'];
		// les fichiers du sentier sont contenu dans un répertoire spécifique au sentier
		$base_chemin_export = $this->config['export']['chemin_export_tmp'].$this->sluggifierPlus($sentier_titre).DIRECTORY_SEPARATOR;
		$chemin_html = $base_chemin_export.$nom_fichier.'.html';
		$chemin_pdf = $base_chemin_export.$nom_fichier.'.pdf';

		$base_url_export = $this->remplacerCheminParUrl($base_chemin_export);
		$url_export_tmp = $base_url_export.DIRECTORY_SEPARATOR.$nom_fichier.'.html';

		// sauvegarde dans un fichier qui sera accessible directement pour le script de conversion par son url
		file_put_contents($chemin_html, $panneau_html);

		//debug
		//echo file_get_contents($chemin_html);exit;

		// Appel au web service de conversion et sauvegarde
		$pdf_converti = file_get_contents(sprintf($this->config['export']['pdf_export_url'], urlencode($url_export_tmp), 1748, 2481));
		file_put_contents($chemin_pdf, $pdf_converti);
		chmod($chemin_pdf, 0777);

		// suppression du fichier html temporaire
		unlink($chemin_html);
	}

	protected function deciderActionExportSentier($sentier_titre) {
		$sentier_titre_slug = $this->sluggifierPlus($sentier_titre);
		$chemin_dossier_sentier = $this->config['export']['chemin_export_tmp'].$sentier_titre_slug.DIRECTORY_SEPARATOR;

		$this->preparerExportSentier($sentier_titre);

		$fiches_a_exporter = glob($chemin_dossier_sentier."*.tmp");

		while(!empty($fiches_a_exporter)) {
			$fiche_a_exporter = array_shift($fiches_a_exporter);
			$fiche = rtrim($fiche_a_exporter, '.tmp');

			$parties_nom_fichier = explode(DIRECTORY_SEPARATOR, $fiche);
			$nom_fichier = end($parties_nom_fichier);

			list($referentiel, $num_tax) = $this->splitNt($nom_fichier);
			$this->enregistrerFichePourExportSentier($sentier_titre, $referentiel, $num_tax);
			unlink($fiche_a_exporter);
		}

		$pdfs = implode(' ', glob($chemin_dossier_sentier."panneau-smartflore*.pdf"));
		$commande = '/usr/bin/pdftk '.$pdfs.' cat output '.$chemin_dossier_sentier.$sentier_titre_slug.'.pdf';
		exec($commande);

		header("Content-type:application/pdf; charset=utf-8");
		// TODO: envoyer la taille dans le header parce que c'est mieux !
		// Supprimer les espaces et les points permet d'avoir un nom de fichier pas trop dégeulasse lors du téléchargement par le navigateur
		header("Content-Disposition:attachment;filename=".$sentier_titre_slug.".pdf");
		header('Content-Length: '.filesize($chemin_dossier_sentier.$sentier_titre_slug.'.pdf'));

		echo file_get_contents($chemin_dossier_sentier.$sentier_titre_slug.'.pdf');

		// supprime le bordel laissé par l'export
		$contenuDossier = glob($chemin_dossier_sentier . "*");
		foreach($contenuDossier as $fichier){ // iterate files
			if (is_file($fichier)) {
				unlink($fichier);
			}
		}

		exit;
	}

	protected function preparerExportSentier($sentier_titre) {
		$chemin_dossier_sentier = $this->config['export']['chemin_export_tmp'].$this->sluggifierPlus($sentier_titre).DIRECTORY_SEPARATOR;
		// TODO: vérifier les erreurs
		@mkdir($chemin_dossier_sentier);
		chmod($chemin_dossier_sentier, 0777);

		$requete_fiches_a_sentier = 'SELECT * FROM '.$this->config['bdd']['table_prefixe'].'_triples '.
				'WHERE property = "'.$this->triple_sentier_fiche.'" '.
				'AND resource = '.$this->bdd->quote($sentier_titre);

		$res = $this->bdd->query($requete_fiches_a_sentier);
		$res = $res->fetchAll(PDO::FETCH_ASSOC);

		// WTF de stratégie de papou ???
		foreach($res as $fiche) {
			$nom_fichier = $fiche['value'].'.tmp';
			touch($chemin_dossier_sentier.$nom_fichier);
		}
	}

	protected function chargerInformationsFiche($referentiel, $num_tax) {

		// informations sur le nom scientifique et la famille
		$url_sci_tpl = $this->config['eflore']['url_base'].$this->config['eflore']['infos_taxons_export_url'];
		$url_sci = sprintf($url_sci_tpl, strtolower($referentiel), 'nt:'.$num_tax);
		//echo $url_sci;exit;
		$infos_s = @file_get_contents($url_sci);
		$infos_sci = json_decode($infos_s, true);

		$num_nom = $infos_sci['id'];
		$nom_sci = $infos_sci['nom_complet'];

		$nom_verna = "";
		// Des fois il n'existe pas de référentiel de nom vernaculaire pour le référentiel scientifique donné
		if(!empty($this->config['eflore']['referentiel_verna_'.$referentiel])) {
			$referentiel_verna = $this->config['eflore']['referentiel_verna_'.$referentiel];
			// informations sur les noms vernaculaires
			$url_verna_tpl = $this->config['eflore']['url_base'].$this->config['eflore']['infos_noms_vernaculaires_url'];
			$url_verna = sprintf($url_verna_tpl, strtolower($referentiel_verna), $num_tax);

			$infos_v = @file_get_contents($url_verna);
			$infos_verna = json_decode($infos_v, true);

			if(!empty($infos_verna['resultat'])) {
				$infos_nom_verna = (array_shift($infos_verna['resultat']));
				$nom_verna = $infos_nom_verna['nom'];
			}
		}

		// Lien vers le qr code (existe t'il toujours ? Ne vaudrait il mieux pas appeler un web service plutot que le fichier du cache ?)
		$qr_code_url_tpl = $this->config['eflore']['qr_code_url'];
		$qr_code_url = sprintf($qr_code_url_tpl, strtolower($referentiel), $num_nom);

		// Description du wiki
		$description_url_tpl = $this->config['export']['description_url_tpl'];
		$description_url = sprintf($description_url_tpl, strtoupper($referentiel), $num_tax);

		// Si jamais il n'existe pas de description l'indice 'texte' du tableau existe quand même et est vide
		$desc = @file_get_contents($description_url);
		$description = json_decode($desc, true);

		// Appel d'identiplante pour obtenir l'image la mieux votée selon le protocole d'identification
		$meilleure_image_url_tpl = $this->config['export']['meilleure_image_url_tpl'];
		$meilleure_image_url = sprintf($meilleure_image_url_tpl, strtolower($referentiel), $num_nom);
		//echo "MIU: $meilleure_image_url"; exit;

		$meilleure_im = @file_get_contents($meilleure_image_url);
		$meilleure_image = json_decode($meilleure_im, true);

		$meilleure_image_src = "";
		if(!empty($meilleure_image['resultats'])) {
			$meilleure_image_infos = array_shift($meilleure_image['resultats']);
			$meilleure_image_src = $meilleure_image_infos['binaire.href'];
		}

		// en cas d'indisponibilité du nom vernaculaire, le nom scientifique
		// peut être affiché à la place
		//$nom_sci = "Capparis sepiaria var. fischeri (Pax) DeWolf"; // 44
		//$nom_sci = "Capparis sepiaria var. fischeri (Pax) DeWolf Couscous 3000"; // 58
		//$nom_sci = "Capparis sepiaria var. fischer"; // 30
		//$nom_sci = "Capparis sepiaria var. fischer cousc"; // 36
		$nom_a_afficher = $nom_verna;
		if ($nom_a_afficher == "") {
			$nom_a_afficher = $nom_sci;
		}

		$infos_fiche = array(
				'url_qr_code' => $qr_code_url,
				'url_illustration' => $meilleure_image_src,
				'nom_vernaculaire' => $this->convertirEnEntitesHtmlSaufTags($nom_verna),
				'nom_sci' => $this->convertirEnEntitesHtmlSaufTags($nom_sci),
				'num_nom' => $num_nom,
				'famille' => $this->convertirEnEntitesHtmlSaufTags($infos_sci['famille']),
				'description' => $this->convertirEnEntitesHtmlSaufTags($description['texte']),
				'description_classe' => $this->getClasseDescription(strlen($description['texte'])),
				'nom_classe' => $this->getClasseNomVernaOuSci(strlen($nom_a_afficher))
		);

		return $infos_fiche;
	}

	/**
	 * Retourne une classe CSS pour la description (texte du panneau) en
	 * fonction de la longueur de celle-ci
	 */
	protected function getClasseDescription($longueur) {
		$classe = "panneau-description-normale";

		if ($longueur >= 1300 && $longueur < 2000) {
			$classe = "panneau-description-grande";
		}
		if ($longueur >= 2000) {
			$classe = "panneau-description-tres-grande";
		}

		return $classe;
	}

	/**
	 * Retourne une classe CSS pour le nom de la plante (vernaculaire ou à
	 * défaut scientifique, en fonction de sa longueur
	 */
	protected function getClasseNomVernaOuSci($longueur) {
		$classe = "panneau-nom-vernaculaire-normal";

		if ($longueur >= 36 && $longueur < 46) {
			$classe = "panneau-nom-vernaculaire-grand";
		}
		if ($longueur >= 46) {
			$classe = "panneau-nom-vernaculaire-tres-grand";
		}

		return $classe;
	}

	/**
	 * Charge le squelette PHP/ HTML avec les données fournies, et le retourne
	 * sous forme de string
	 */
	protected function remplirSquelette($squelette, $variables) {
		extract($variables);

		ob_start();
		include 'squelettes/'.$squelette.'.tpl.html';
		$sortie = ob_get_contents();
		@ob_end_clean();

		return $sortie;
	}
}

$export = new Export();
?>
