smartFormApp.service('etatApplicationService', function($http) {

	// Etat de la recherche, du chargement etc...
	var etatApplicationService = {};

	etatApplicationService.referentiels = config.referentiels;
	etatApplicationService.infosReferentiels = config.infos_referentiels;

	etatApplicationService.recherche = {};
	etatApplicationService.recherche.texte = "";
	etatApplicationService.recherche.fichesExistantes = false;
	etatApplicationService.recherche.referentiel = etatApplicationService.referentiels[0];
	etatApplicationService.recherche.nomVernaculaire = false;
	etatApplicationService.recherche.referentielVerna = etatApplicationService.infosReferentiels[etatApplicationService.recherche.referentiel].noms_vernaculaires;


	etatApplicationService.rechercheModifiee = false;
	etatApplicationService.premierChargement = true;

	// etat de l'utilisateur
	// @WTF pourquoi ne pas utiliser boiteUtilisateurControleur.initialiserUtilisateurVide() ?
	// @WTF en fait initialiserUtilisateurVide devrait faire partie de etatApplicationService. L'idéal serait un userService
	etatApplicationService.utilisateur = {};
	etatApplicationService.utilisateur.connecte = false;
	etatApplicationService.utilisateur.id = "";
	etatApplicationService.utilisateur.prenom = "";
	etatApplicationService.utilisateur.nom = "";
	etatApplicationService.utilisateur.intitule = "";
	etatApplicationService.utilisateur.courriel = "";

	// SSO
	etatApplicationService.jeton = null;
	etatApplicationService.idJeton = "";
	etatApplicationService.dureeJeton = 0;

	etatApplicationService.voirTousLesSentiers = false;

	etatApplicationService.connecterUtilisateur  = function(utilisateur, surSucces, surErreur) {
		var url_service = config.url_service_annuaire.replace("{service}", "connexion");
		// Besoin d'un objet particulier ici car sinon angular poste du json
		$http.get(url_service + "?login=" + utilisateur.courriel + "&password=" + encodeURIComponent(utilisateur.mdp))
		.success(function(data, status, headers, config) {
			surSucces(data);
		})
		.error(function(data, status, headers, config) {
			surErreur();
		});
	};

	etatApplicationService.connaitreEtatUtilisateur = function(surSucces, surErreur) {
		var url_service = config.url_service_annuaire.replace("{service}", "identite");
		$http.get(url_service).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};

	etatApplicationService.deconnecterUtilisateur = function(surSucces, surErreur) {
		var url_service = config.url_service_annuaire.replace("{service}", "deconnexion");
		$http.get(url_service).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};

	// http://stackoverflow.com/questions/979975/how-to-get-the-value-from-the-url-parameter
	etatApplicationService.queryString = function () {
		// This function is anonymous, is executed immediately and
		// the return value is assigned to QueryString!
		var query_string = {};
		var query = decodeURIComponent(window.location.search.substring(1));
		var vars = query.split("&");
		for (var i=0;i<vars.length;i++) {
			var pair = vars[i].split("=");
	        // If first entry with this name
			if (typeof query_string[pair[0]] === "undefined") {
				query_string[pair[0]] = pair[1];
				// If second entry with this name
			} else if (typeof query_string[pair[0]] === "string") {
				var arr = [ query_string[pair[0]], pair[1] ];
				query_string[pair[0]] = arr;
				// If third or later entry with this name
			} else {
	      query_string[pair[0]].push(pair[1]);
			}
		}
	    return query_string;
	} ();

	etatApplicationService.getHeadersAuth = function() {
		headers_requete = {};
		headers_requete[config.auth_header] = etatApplicationService.jeton;
		return headers_requete;
	};

	return etatApplicationService;
});
