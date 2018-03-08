<?php
// declare(encoding='UTF-8');
/**
 * Classe mère des web services de smartFlore
 *
 * @category	php 5.2
 * @package		smart-form
 * @author		Aurélien Peronnet < aurelien@tela-botanica.org>
 * @copyright	Copyright (c) 2015, Tela Botanica (accueil@tela-botanica.org)
 * @license		http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
 * @license		http://www.gnu.org/licenses/gpl.html Licence GNU-GPL
 */
class SmartFloreService {

	protected $config = null;
	protected $bdd = null;

	protected $triple_sentier = "smartFlore.sentiers";
	protected $triple_sentier_fiche = "smartFlore.sentiers.fiche";
	protected $triple_sentier_localisation = "smartFlore.sentiers.localisation";
	protected $triple_sentier_dessin = "smartFlore.sentiers.dessin";
	protected $triple_sentier_etat = "smartFlore.sentiers.etat";
	protected $triple_sentier_date_derniere_modif = "smartFlore.sentiers.date_derniere_modif";
	protected $triple_sentier_date_creation = "smartFlore.sentiers.date_creation";
	protected $triple_sentier_date_suppression = "smartFlore.sentiers.date_suppression";
	protected $triple_sentier_meta = "smartFlore.sentiers.meta";

	protected $triple_favoris_fiche = "smartFlore.favoris.fiche";

	protected $triple_evenement = "smartFlore.evenements";
	protected $triple_evenement_sentier_ajout = "smartFlore.evenements.sentiers.ajout";
	protected $triple_evenement_sentier_fiche_ajout = "smartFlore.evenements.sentiers.fiche.ajout";
	protected $triple_evenement_favoris_ajout = "smartFlore.evenements.favoris.ajout";

	protected $auth_header = 'Authorization';

	/** Utilisateur en cours, identifié par un jeton SSO */
	protected $utilisateur;

	public function __construct() {
		// sequoia est relou !
		ini_set('default_charset', 'utf-8');
		$this->config = parse_ini_file('config.ini', true);

		// sous php-cgi le module retire le header Authorization
		// donc en attendant de migrer php de sequoia vers une version récente
		// on passe la variable dans un autre header
		$this->auth_header = !empty($this->config['auth']['header']) ? $this->config['auth']['header'] : $this->auth_header;
		try {
			$this->bdd = new PDO('mysql:host='. $this->config['bdd']['host'].';dbname='. $this->config['bdd']['db'], $this->config['bdd']['user'],  $this->config['bdd']['pass']);
			$this->bdd->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$this->bdd->exec("SET CHARACTER SET utf8");
		}
		catch (PDOException $e) {
			$error_message = $e->getMessage();
			echo "Erreur de connexion à la base de données";
			exit();
		}

		$this->init();
	}

	function __destruct() {
		$this->bdd = null;
	}

	// ---------------------------------------------------------------------------------------------
	//
	//	FONCTIONS REST
	//
	// ---------------------------------------------------------------------------------------------
	function init() {
		$methode = $_SERVER['REQUEST_METHOD'];
		// Certaines version de php remplissent PATH_INFO et d'autres ORIG_PATH_INFO en cas de redirection
		$path_info = !empty($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : (!empty($_SERVER['ORIG_PATH_INFO']) ? $_SERVER['ORIG_PATH_INFO'] : '');
		$requete = explode("/", substr(@$_SERVER['PATH_INFO'], 1));

		// Récupération d'un éventuel jeton SSO
		$this->getUtilisateurIdentifie();

		switch ($methode) {
			case 'PUT':
				$this->verifierAuthentification();
				$this->put($requete, $this->retrouverInputData());
				break;
			case 'POST':
				$this->verifierAuthentification();
				$this->post($requete, $this->retrouverInputData());
				break;
			case 'GET':
				$this->get($requete);
				break;
			case 'HEAD':
				$this->head($requete);
				break;
			case 'DELETE':
				$this->verifierAuthentification();
				$this->delete($requete, $this->retrouverInputData());
				break;
			case 'OPTIONS':
				$this->options($requete);
				break;
			default:
				$this->error(400, "unsupported method $method");
				break;
		}
	}

	// Fonctions à surcharger dans les classes filles si besoin
	// fonctions pseudo abstraites
	function put($requete, $data) {

	}

	function post($requete, $data) {

	}

	function get($requete) {

	}

	function head($requete) {

	}

	function delete($requete, $data) {

	}

	function options($requete) {

	}

	function error($code, $texte) {
		http_response_code($code);
		echo $texte;
		exit;
	}

	// ---------------------------------------------------------------------------------------------
	//
	//	FONCTIONS SPECIFIQUES À L'AUTHENTIFICATION SSO
	//
	// ---------------------------------------------------------------------------------------------

	/**
	 * Termine le programme si l'utilisateur n'est pas identifié par un jeton SSO
	 */
	protected function verifierAuthentification() {
		if ($this->utilisateur == null) {
			$this->error(401, "vous devez être authentifié pour utiliser ce service");
			exit;
		}
	}

	/**
	 * Retourne true si l'utilisateur en cours fait partie de la liste des
	 * admins, définie dans config.ini
	 */
	protected function estAdmin() {
		$listeAdmins = explode(',', $this->config['auth']['admins']);
		return in_array($this->utilisateur['courriel'], $listeAdmins);
	}

	/**
	 * Recherche un jeton SSO dans l'entête HTTP "Authorization", vérifie ce
	 * jeton auprès de l'annuaire et en cas de succès charge les informations
	 * de l'utilisateur associé dans $this->utilisateur
	 *
	 * @return Array un profil utilisateur ou null
	 */
	protected function getUtilisateurIdentifie() {
		$utilisateur = null;
		// lecture du jeton
		$jeton = $this->lireJetonEntete();
		if ($jeton != null) {
			// validation par l'annuaire
			$valide = $this->verifierJeton($jeton);
			if ($valide === true) {
				// décodage du courriel utilisateur depuis le jeton
				$donneesJeton = $this->decoderJeton($jeton);
				if ($donneesJeton != null && $donneesJeton["sub"] != "") {
					// récupération de l'utilisateur
					$utilisateur = array(
						"courriel" => $donneesJeton["sub"],
						"id" => $donneesJeton["id"]
					);
				}
			}
		}
		$this->utilisateur = $utilisateur;
	}



	/**
	 * Essaye de trouver un jeton JWT non vide dans l'entête HTTP (par défaut
	 * "Authorization")
	 *
	 * @return String un jeton JWT ou null
	 */
	protected function lireJetonEntete() {
		$jwt = null;
		if (! function_exists('apache_request_headers')) {
			function apache_request_headers() {
				$headers = array();
				foreach ($_SERVER as $name => $value) {
					if (substr($name, 0, 5) == 'HTTP_') {
						$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
					}
				}
				return $headers;
			}
		}
		$headers = apache_request_headers();
		if (isset($headers[$this->auth_header]) && ($headers[$this->auth_header] != "")) {
			$jwt = $headers[$this->auth_header];
		}
		return $jwt;
	}

	/**
	 * Vérifie un jeton auprès de l'annuaire
	 *
	 * @param String $jeton un jeton JWT
	 * @return true si le jeton est vérifié, false sinon
	 */
	protected function verifierJeton($jeton) {
		$urlServiceVerification = $this->config['auth']['auth_url']  . "/verifierjeton";
		$urlServiceVerification .= "?token=" . $jeton;

		// file_get_contents râle si le certificat HTTPS est auto-signé
		//$retour = file_get_contents($urlServiceVerification);

		// curl avec les options suivantes ignore le pb de certificat (pour tester en local)
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $urlServiceVerification);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		// équivalent de "-k"
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$retour = curl_exec($ch);
		curl_close($ch);

		$retour = json_decode($retour, true);

		return ($retour === true);
	}


	/**
	 * Decode a string with URL-safe Base64.
	 * copié depuis firebase/jwt
	 *
	 * @param string $input A Base64 encoded string
	 * @return string A decoded string
	 */
	protected function urlsafeB64Decode($input) {
		$remainder = strlen($input) % 4;
		if ($remainder) {
			$padlen = 4 - $remainder;
			$input .= str_repeat('=', $padlen);
		}
		return base64_decode(strtr($input, '-_', '+/'));
	}

	/**
	 * Décode un jeton JWT (SSO) précédemment validé et retourne les infos
	 * qu'il contient (payload / claims)
	 * @param String $jeton un jeton JWT précédemment validé
	 */
	protected function decoderJeton($jeton) {
		$parts = explode('.', $jeton);
		$payload = $parts[1];
		$payload = $this->urlsafeB64Decode($payload);
		$payload = json_decode($payload, true);

		return $payload;
	}

	function retrouverInputData() {
		$input_fmt = "";
		$input = file_get_contents('php://input', 'r');

		return json_decode($input, true);
	}


	// ---------------------------------------------------------------------------------------------
	//
	//	FONCTIONS SPECIFIQUES AU WIKI
	//
	// ---------------------------------------------------------------------------------------------
	function splitNt($page) {
		$page = str_replace('SmartFlore', '', $page);
		return preg_split("/nt/", $page);
	}

	/**
	 * Retourne le referentiel et le numero taxonomique à partir d'un indentifiant d'individu
	 *
	 * Ex: mange 'SmartFloreBDTFXnt6200#1' et recrache array('BDTFX', '6200')
	 *
	 * @param      string  $individu_id  (ex: SmartFloreBDTFXnt6200#1)
	 *
	 * @return     array
	 */
	function digestIndividuId($individu_id) {
		$infos = str_replace('smartflore', '', strtolower($individu_id));
		$infos = preg_replace('/#\d+$/i', '', $infos);

		return preg_split("/nt/", $infos);
	}

	function getPagesWikiParRechercheFloue($recherche) {
		$tpl_quote = $this->bdd->quote($recherche['noms_pages']);
		return $this->getPagesWiki('tag LIKE '.$tpl_quote.' ', $recherche['debut'], $recherche['limite']);
	}

	function getPagesWikiParRechercheExacte($recherche) {
		if(empty($recherche['noms_pages'])) {
			$retour = array(array(), 0);
		} else {
			$retour = $this->getPagesWiki('tag IN ('.implode(',', $recherche['noms_pages']).')', $recherche['debut'], $recherche['limite']);
		}
		return $retour;
	}

	private function getPagesWiki($condition, $debut = null, $limite = null) {

		if(!empty($condition)) {
			$condition .= 'AND ';
		}
		// Seulement les dernières révisions des pages
		$condition .= ' latest = "Y" ';

		$champs = "id, tag, time, owner, user, latest";

		$requete = 'SELECT '.$champs.', COUNT(tag) as nb_revisions '.
				'FROM '.$this->config['bdd']['table_prefixe'].'_pages '.
				'WHERE '.$condition.' '.
				'GROUP BY tag '.
				'ORDER BY nb_revisions DESC '.
				($debut !== null ? 'LIMIT '.$debut.', '.$limite : '');

		$res = $this->bdd->query($requete);
		$res = $res->fetchAll(PDO::FETCH_ASSOC);

		$comptage = 'SELECT COUNT(DISTINCT tag) as nb_pages '.
				'FROM '.$this->config['bdd']['table_prefixe'].'_pages '.
				'WHERE '.$condition.' ';

		$res_comptage = $this->bdd->query($comptage);
		$res_comptage = $res_comptage->fetch(PDO::FETCH_ASSOC);

		return array($res, $res_comptage['nb_pages']);
	}

	protected function completerPagesParInfosTaxon($pages_wiki) {

		$infos_indexees_par_referentiel_nt = array();
		$infos_indexees_par_referentiel_nn = array();

		$retour = array('resultats' => array(), 'fiches_a_num_nom' => array());

		$nts = array();
		foreach($pages_wiki as $resultat) {
			list($referentiel, $nt) = $this->splitNt($resultat['tag']);
			if(empty($nts)) {
				$nts[$referentiel] = array();
			}
			$nts[$referentiel][] = $nt;
			$resultat['existe'] = !empty($resultat['nb_revisions']) && $resultat['nb_revisions'] > 0;
			$resultat['favoris'] = false;
			$infos_indexees_par_referentiel_nt[$referentiel.$nt] = $resultat;
		}

		$url_eflore_tpl = $this->config['eflore']['url_base'] . $this->config['eflore']['infos_taxons_url'];

		// $nts est un tableau indexé par référentiel, puis par nt
		// la fonction renvoie un tableau indexé par referentiel et nn pour pouvoir avoir tout
		// de même autant de résultats que de noms (et pas de taxons)
		foreach($nts as $referentiel => $nts_a_ref) {
			if(!empty($referentiel)) {
				$num_tax_a_nums_noms = array();
				$nts_ref_tranches = array_chunk($nts_a_ref, 99, true);
				foreach($nts_ref_tranches as $tranche) {

					$url = sprintf($url_eflore_tpl, strtolower($referentiel), implode(',', $tranche));
					$infos = file_get_contents($url);
					$infos = json_decode($infos, true);

					foreach($infos['resultat'] as $num_nom => $infos_a_nt) {

						$nom_fiche = $this->formaterPageNom($referentiel, $infos_a_nt['num_taxonomique']);
						$retour['fiches_a_num_nom'][$nom_fiche] = $num_nom;

						$infos_a_nt['num_nom'] = $num_nom;
						$infos_a_nt['referentiel'] = $referentiel;
						$retour['resultats'][$referentiel.$infos_a_nt['num_nom']] = $infos_indexees_par_referentiel_nt[$referentiel.$infos_a_nt['num_taxonomique']];
						$retour['resultats'][$referentiel.$infos_a_nt['num_nom']]['infos_taxon'] = $infos_a_nt;

						$num_tax_a_nums_noms[$infos_a_nt['num_taxonomique']][] = $infos_a_nt['num_nom'];
					}
				}

				$cle_ref = 'referentiel_verna_'.strtolower($referentiel);
				if(!empty($this->config['eflore'][$cle_ref])) {
					// retour est modifié par référence dans la fonction compléter par noms vernaculaires
					// TODO: faire ceci aussi souvent que possible !
					$this->completerPagesParNomsVernaculaires($referentiel, $this->config['eflore'][$cle_ref], $num_tax_a_nums_noms, $retour);
				}
			}
		}

		return $retour;
	}

	// Attention le tableau retour est passé par référence
	function completerPagesParNomsVernaculaires($referentiel, $referentiel_verna, $nts_a_nn, &$retour) {
		$url_eflore_tpl = $this->config['eflore']['url_base'] . $this->config['eflore']['infos_noms_vernaculaires_url'];
		$url = sprintf($url_eflore_tpl, strtolower($referentiel_verna), implode(',', array_keys($nts_a_nn)));

		$infos = @file_get_contents($url);
		$infos = json_decode($infos, true);

		if(!empty($infos['resultat'])) {
			foreach($infos['resultat'] as $num_nom_verna => $infos_a_num_nom) {
				if(!empty($nts_a_nn[$infos_a_num_nom['num_taxon']])) {
					$nums_noms_a_nt = $nts_a_nn[$infos_a_num_nom['num_taxon']];

					foreach($nums_noms_a_nt as $num_nom) {
						if(!empty($retour['resultats'][$referentiel.$num_nom])) {
							$retour['resultats'][$referentiel.$num_nom]['infos_taxon']['noms_vernaculaires'][] = $infos_a_num_nom['nom'];
						}
					}

				}
			}
		}
	}

	function formaterPageNom($referentiel, $nt) {
		return 'SmartFlore'.strtoupper($referentiel).'nt'.$nt;
	}

	function getFavorisPourUtilisateur($utilisateur, $tag_fiches = array()) {
		$requete = 'SELECT * '.
				'FROM '.$this->config['bdd']['table_prefixe'].'_triples '.
				'WHERE value = '.$this->bdd->quote($utilisateur).' '.
				'AND property = "'.$this->triple_favoris_fiche.'" '.
				(!empty($tag_fiches) ? 'AND resource IN ('.implode(',', array_map(array($this->bdd, 'quote'), $tag_fiches)).')' : '');

		$res = $this->bdd->query($requete);
		$res = $res->fetchAll(PDO::FETCH_ASSOC);

		return $res;
	}

	protected function consulterRechercheNomsSciEflore($recherche) {
		$url_eflore_tpl = $this->config['eflore']['url_base'] . $this->config['eflore']['recherche_noms_url'];
		$url = sprintf($url_eflore_tpl, strtolower($recherche['referentiel']), 'etendue', urlencode($recherche['recherche'].'%'), $recherche['debut'], $recherche['limite']);

		// Quand il n'y pas de résultats eflore renvoie une erreur 404 (l'imbécile !)
		// or le cas où l'on n'a pas de résultats est parfaitement valide
		$infos = @file_get_contents($url);
		$infos = json_decode($infos, true);

		if(empty($infos['entete']) || $infos['entete']['total'] == 0) {
			// rien trouvé ? peut être une faute de frappe, on retente avec la recherche floue
			$url = sprintf($url_eflore_tpl, strtolower($recherche['referentiel']), 'floue', urlencode($recherche['recherche'].'%'), $recherche['debut'], $recherche['limite']);

			$infos = @file_get_contents($url);
			$infos = json_decode($infos, true);
		}

		return $infos;
	}

	protected function consulterRechercheNomsVernaEflore($recherche) {
		$url_eflore_verna_tpl = $this->config['eflore']['url_base'] . $this->config['eflore']['recherche_noms_vernaculaires_url'];
		$referentiel_verna = $this->config['eflore']['referentiel_verna_'.strtolower($recherche['referentiel'])];
		$url_verna = sprintf($url_eflore_verna_tpl, $referentiel_verna, urlencode($recherche['recherche'].'%'), $recherche['debut'], $recherche['limite']);

		// Quand il n'y pas de résultats eflore renvoie une erreur 404 (l'imbécile !)
		// or le cas où l'on n'a pas de résultats est parfaitement valide
		$infos_verna = @file_get_contents($url_verna);
		$infos_verna = json_decode($infos_verna, true);

		return $infos_verna;
	}

	// ---------------------------------------------------------------------------------------------
	//
	//	FONCTIONS SPECIFIQUES AUX EVENEMENTS
	//
	// ---------------------------------------------------------------------------------------------
	function enregistrerEvenement($evenement, $cible) {
		$cible = $this->bdd->quote(json_encode($cible));

		date_default_timezone_set('Europe/Paris');

		$requete_insertion = 'INSERT INTO '.$this->config['bdd']['table_prefixe'].'_triples '.
				'(resource, property, value) VALUES '.
				' ("'.date('Y-m-d H:i:s').'","'.$evenement.'", '.$cible.') ';

		$res_insertion = $this->bdd->exec($requete_insertion);
		$retour = ($res_insertion !== false) ? 'OK' : false;
	}

	function getEvenements($debut = 0, $limite = 100) {
		$requete = 'SELECT * '.
				'FROM '.$this->config['bdd']['table_prefixe'].'_triples '.
				'WHERE property LIKE "'.$this->triple_evenement.'%" '.
				'ORDER BY resource DESC '.
				'LIMIT '.$debut.','.$limite.' ';

		$res = $this->bdd->query($requete);
		$res = $res->fetchAll(PDO::FETCH_ASSOC);

		return $res;
	}

	function miseEnFormeInfosSentiers($array) {
		$sentiersNommes = array();

		foreach($array as $r) {
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
					$sentiersNommes[$nomSentier]['id'] = $r['id'];
					break;
				case $this->triple_sentier_meta:
					$sentiersNommes[$nomSentier]['meta'] = $r['value'];
					break;
				case $this->triple_sentier_etat:
					$sentiersNommes[$nomSentier]['etat'] = $r['value'];
					break;
				case $this->triple_sentier_date_creation:
					$sentiersNommes[$nomSentier]['dateCreation'] = $r['value'];
					break;
				case $this->triple_sentier_date_derniere_modif:
					$sentiersNommes[$nomSentier]['dateDerniereModif'] = $r['value'];
					break;
				case $this->triple_sentier_date_suppression:
					$sentiersNommes[$nomSentier]['dateSuppression'] = $r['value'];
					break;
				case $this->triple_evenement_sentier_ajout:
					preg_match('/{"utilisateur":".+","utilisateur_courriel":"(.+@.+)","titre":"(.+)"}/', $r['value'], $matches);
					if (!empty($matches[1]) && !empty($matches[2] && array_key_exists($matches[2], $sentiersNommes))) {
						$sentiersNommes[$matches[2]]['auteurEmail'] = $matches[1];
					}
					break;
			}
		}

		return $sentiersNommes;
	}
}

// echo pre print_r true -> epprt
function epprt($obj) {
	echo '<pre>'.print_r($obj, true).'</pre>';
}

// Pour compenser d'éventuels manques des anciennes version de php
if (!function_exists('http_response_code')) {
	function http_response_code($code = NULL) {
		if ($code !== NULL) {
			switch ($code) {
				case 100: $text = 'Continue'; break;
				case 101: $text = 'Switching Protocols'; break;
				case 200: $text = 'OK'; break;
				case 201: $text = 'Created'; break;
				case 202: $text = 'Accepted'; break;
				case 203: $text = 'Non-Authoritative Information'; break;
				case 204: $text = 'No Content'; break;
				case 205: $text = 'Reset Content'; break;
				case 206: $text = 'Partial Content'; break;
				case 300: $text = 'Multiple Choices'; break;
				case 301: $text = 'Moved Permanently'; break;
				case 302: $text = 'Moved Temporarily'; break;
				case 303: $text = 'See Other'; break;
				case 304: $text = 'Not Modified'; break;
				case 305: $text = 'Use Proxy'; break;
				case 400: $text = 'Bad Request'; break;
				case 401: $text = 'Unauthorized'; break;
				case 402: $text = 'Payment Required'; break;
				case 403: $text = 'Forbidden'; break;
				case 404: $text = 'Not Found'; break;
				case 405: $text = 'Method Not Allowed'; break;
				case 406: $text = 'Not Acceptable'; break;
				case 407: $text = 'Proxy Authentication Required'; break;
				case 408: $text = 'Request Time-out'; break;
				case 409: $text = 'Conflict'; break;
				case 410: $text = 'Gone'; break;
				case 411: $text = 'Length Required'; break;
				case 412: $text = 'Precondition Failed'; break;
				case 413: $text = 'Request Entity Too Large'; break;
				case 414: $text = 'Request-URI Too Large'; break;
				case 415: $text = 'Unsupported Media Type'; break;
				case 500: $text = 'Internal Server Error'; break;
				case 501: $text = 'Not Implemented'; break;
				case 502: $text = 'Bad Gateway'; break;
				case 503: $text = 'Service Unavailable'; break;
				case 504: $text = 'Gateway Time-out'; break;
				case 505: $text = 'HTTP Version not supported'; break;
				default:
					exit('Unknown http status code "' . htmlentities($code) . '"');
					break;
			}

			$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
			header($protocol . ' ' . $code . ' ' . $text);
			$GLOBALS['http_response_code'] = $code;
		} else {
			$code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);
		}
		return $code;
	}
}
?>
