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
		if(!empty($_GET['referentiel']) && !empty($_GET['num_tax'])) {
			// Export d'une seule fiche (éventuellement accompagnée d'un nom de sentier à intégrer dans le titre)
			$sentier = !empty($_GET['sentierTitre']) ? $_GET['sentierTitre'] : '';
			$this->getExportFiche($_GET['referentiel'], $_GET['num_tax'], $sentier);
		} else if(!empty($_GET['sentierTitre'])) {
			// export de toutes les fiches d'un sentier
			$this->getExportSentier($_GET['sentierTitre']);
		} else {
			$this->error(400, "Vous devez spécifier un nom de sentier ou bien un référentiel avec un numéro taxonomique");
		}
	}
	
	function getExportFiche($referentiel, $num_tax, $sentier = '') {
					
		$infos_fiche = $this->chargerInformationsFiche($referentiel, $num_tax);
		$infos_fiche['nom_sentier'] = ucfirst($sentier);
		$infos_fiche['titre'] = $sentier;
		
		$infos_fiche['base_style_url'] = $this->remplacerCheminParUrl(dirname(__FILE__).DIRECTORY_SEPARATOR.'squelettes').DIRECTORY_SEPARATOR;

		$panneau_html = $this->remplirSquelette('panneau', $infos_fiche);
		$nom_fichier = 'panneau-smartflore-'.$this->sluggifierSimple($sentier).'-'.$referentiel.'-'.$infos_fiche['num_nom'];
		// Attention le chemin d'export temporaire doit se trouver au dessus de la racine des documents du serveur afin d'être convertible en url
		$chemin_html = $this->config['export']['chemin_export_tmp'].$nom_fichier.'.html';
		// sauvegarde dans un fichier qui sera accessible directement pour le script de conversion par son url
		file_put_contents($chemin_html, $panneau_html);
		
		// à décommenter pour afficher la fiche en html
		// echo file_get_contents($this->cheminExportTmp.$nom_fichier.'.html');exit;
		
		header("Content-type:application/pdf");
		// TODO: envoyer la taille dans le header parce que c'est mieux !
		// Supprimer les espaces et les points permet d'avoir un nom de fichier pas trop dégeulasse lors du téléchargement par le navigateur
		header("Content-Disposition:attachment;filename=".$this->sluggifierSimple($infos_fiche['nom_sci']).".pdf");
		
		$url_export_tmp = $this->remplacerCheminParUrl($chemin_html);
		// Impossible d'installer phantomJs sur sequoia alors on appelle un web service de conversion sur agathis
		echo file_get_contents($this->config['export']['pdf_export_url'].$url_export_tmp);
		exit;
	}
	
	private function remplacerCheminParUrl($chemin) {
		$chemin = realpath($chemin);
		return 'http://'.str_replace(realpath($_SERVER['DOCUMENT_ROOT']), $_SERVER['HTTP_HOST'], $chemin);
	}
	
	private function sluggifierSimple($chaine) {
		return str_replace(array(' ','.'), array('_', ''), $chaine);
	}
	
	private function getExportSentier($sentier) {
		$this->error(501, "Pas encore implémenté");
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
				'nom_vernaculaire' => $nom_verna,
				'nom_sci' => $nom_sci,
				'num_nom' => $num_nom,
				'famille' => $infos_sci['famille'],
				'description' => $description['texte']
		);
		
		return $infos_fiche;
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