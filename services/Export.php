<?php
require_once('SmartFloreService.php');

// declare(encoding='UTF-8');
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
	
	private $cheminExportTmp = null;
	private $apiExportPdf = null;

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
			default:
				$this->error(400, "Aucune commande n'a été spécifiée");
		}
	}
	
	function getExportFiche($referentiel, $num_tax, $sentier_titre = '') {
					
		$infos_fiche = $this->chargerInformationsFiche($referentiel, $num_tax);
		$infos_fiche['nom_sentier'] = $this->convertirEnEntitesHtmlSaufTags(ucfirst($sentier_titre));
		$infos_fiche['titre'] = $this->convertirEnEntitesHtmlSaufTags($sentier_titre);
		
		$infos_fiche['base_style_url'] = $this->remplacerCheminParUrl(dirname(__FILE__).DIRECTORY_SEPARATOR.'squelettes').DIRECTORY_SEPARATOR;

		$panneau_html = $this->remplirSquelette('panneau', $infos_fiche);
		$nom_fichier = 'panneau-smartflore-'.$this->sluggifierPlus($sentier_titre).'-'.$referentiel.'-'.$infos_fiche['num_nom'];
		// Attention le chemin d'export temporaire doit se trouver au dessus de la racine des documents du serveur afin d'être convertible en url
		$chemin_html = $this->config['export']['chemin_export_tmp'].$nom_fichier.'.html';
		// sauvegarde dans un fichier qui sera accessible directement pour le script de conversion par son url
		file_put_contents($chemin_html, $panneau_html);
		
		// à décommenter pour afficher la fiche en html
		// echo file_get_contents($chemin_html);exit;
		
		header("Content-type:application/pdf; charset=utf-8");
		// TODO: envoyer la taille dans le header parce que c'est mieux !
		// Supprimer les espaces et les points permet d'avoir un nom de fichier pas trop dégeulasse lors du téléchargement par le navigateur
		header("Content-Disposition:attachment;filename=".$this->sluggifierSimple($infos_fiche['nom_sci']).".pdf");
		
		$url_export_tmp = urlencode($this->remplacerCheminParUrl($chemin_html));
		// Impossible d'installer phantomJs sur sequoia alors on appelle un web service de conversion sur agathis (les chiffres correspond à une taille de format A5)
		echo file_get_contents(sprintf($this->config['export']['pdf_export_url'], $url_export_tmp, 1748, 2481));
		exit;
	}
	
	private function remplacerCheminParUrl($chemin) {
		$chemin = realpath($chemin);
		return 'http://'.str_replace(realpath($_SERVER['DOCUMENT_ROOT']), $_SERVER['HTTP_HOST'], $chemin);
	}
	
	private function sluggifierSimple($chaine) {
		return str_replace(array(' ','.'), array('_', ''), $chaine);
	}
	
	private function sluggifierPlus($chaine) {
		return $this->sluggifierSimple(preg_replace("/[^A-Za-z0-9 ]/", '', $chaine));
	}
	
	// http://stackoverflow.com/questions/1364933/htmlentities-in-php-but-preserving-html-tags
	private function convertirEnEntitesHtmlSaufTags($chaine) {
		$list = get_html_translation_table(HTML_ENTITIES);
		unset($list['"']);
		unset($list['<']);
		unset($list['>']);
		unset($list['&']);
		
		$search = array_keys($list);
		$values = array_values($list);

		return str_replace($search, $values, $chaine);	
	}
		
	private function getExportSentier($sentier_titre) {	
		// TODO: suivant le nombre de fiches, faire un export en plusieurs fois à travers
		// plusieurs appels ajax ou bien en une fois dans la fonction ci dessous
		$this->deciderActionExportSentier($sentier_titre);
	}
	
	private function deciderActionExportSentier($sentier_titre) {	
		
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
		exit;
		
		//TODO supprimer le bordel laisse par l'export
	}
	
	private function preparerExportSentier($sentier_titre) {
		$chemin_dossier_sentier = $this->config['export']['chemin_export_tmp'].$this->sluggifierPlus($sentier_titre).DIRECTORY_SEPARATOR;
		// TODO: vérifier les erreurs
		@mkdir($chemin_dossier_sentier);
		chmod($chemin_dossier_sentier, 0777);
		
		$requete_fiches_a_sentier = 'SELECT * FROM '.$this->config['bdd']['table_prefixe'].'_triples '.
				'WHERE property = "'.$this->triple_sentier_fiche.'" '.
				'AND value = '.$this->bdd->quote($sentier_titre);
	
		$res = $this->bdd->query($requete_fiches_a_sentier);
		$res = $res->fetchAll(PDO::FETCH_ASSOC);
		
		foreach($res as $fiche) {
			$nom_fichier = $fiche['resource'].'.tmp';
			touch($chemin_dossier_sentier.$nom_fichier);
		}
	}
	
	private function enregistrerFichePourExportSentier($sentier_titre, $referentiel, $num_tax) {
		
		$infos_fiche = $this->chargerInformationsFiche($referentiel, $num_tax);
		$infos_fiche['nom_sentier'] = $this->convertirEnEntitesHtmlSaufTags(ucfirst($sentier_titre));
		$infos_fiche['titre'] = $this->convertirEnEntitesHtmlSaufTags($sentier_titre);
		
		$infos_fiche['base_style_url'] = $this->remplacerCheminParUrl(dirname(__FILE__).DIRECTORY_SEPARATOR.'squelettes').DIRECTORY_SEPARATOR;
		
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
		
		// Appel au web service de conversion et sauvegarde
		$pdf_converti = file_get_contents(sprintf($this->config['export']['pdf_export_url'], urlencode($url_export_tmp), 1748, 2481));
		file_put_contents($chemin_pdf, $pdf_converti);
		chmod($chemin_pdf, 0777);
		
		// suppression du fichier html temporaire
		unlink($chemin_html);
	}
	
	private function chargerInformationsFiche($referentiel, $num_tax) {
		
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
		
		$meilleure_im = @file_get_contents($meilleure_image_url);
		$meilleure_image = json_decode($meilleure_im, true);
		
		$meilleure_image_src = "";
		if(!empty($meilleure_image['resultats'])) {
			$meilleure_image_infos = array_shift($meilleure_image['resultats']);
			$meilleure_image_src = $meilleure_image_infos['binaire.href'];
		}
		
		$infos_fiche = array(
				'url_qr_code' => $qr_code_url,
				'url_illustration' => $meilleure_image_src,
				'nom_vernaculaire' => $this->convertirEnEntitesHtmlSaufTags($nom_verna),
				'nom_sci' => $this->convertirEnEntitesHtmlSaufTags($nom_sci),
				'num_nom' => $num_nom,
				'famille' => $this->convertirEnEntitesHtmlSaufTags($infos_sci['famille']),
				'description' => $this->convertirEnEntitesHtmlSaufTags($description['texte']),
				'description_classe' => $this->getClasseDescription(strlen($description['texte']))
		);
		
		return $infos_fiche;
	}
	
	private function getClasseDescription($longueur) {
		$classe = "";
		if($longueur < 1500) {
			$classe = "panneau-description-normale";
		}
		
		if($longueur >= 1500 && $longueur < 2000) {
			$classe = "panneau-description-grande";
		}
		
		if($longueur >= 2000) {
			$classe = "panneau-description-tres-grande";
		}
		
		return $classe;
	}
	
	private function remplirSquelette($squelette, $variables) {
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