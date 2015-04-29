smartFormApp.service('etatApplicationService', function($http) {
	
	// Etat de la recherche, du chargement etc...
	var etatApplicationService = {};
	etatApplicationService.recherche = {};
	etatApplicationService.recherche.texte = "";
	etatApplicationService.referentiels = config.referentiels;
	etatApplicationService.recherche.referentiel = config.referentiels[0];
	etatApplicationService.recherche.fichesExistantes = false;
	etatApplicationService.rechercheModifiee = false;
	etatApplicationService.premierChargement = true; 
	
	// etat de l'utilisateur
	etatApplicationService.utilisateur = {};
	etatApplicationService.utilisateur.connecte = false;
	etatApplicationService.utilisateur.id = "";
	etatApplicationService.utilisateur.prenom = "";
	etatApplicationService.utilisateur.nom = "";
	etatApplicationService.utilisateur.courriel = "";
	etatApplicationService.utilisateur.nomWiki = "";
	
	etatApplicationService.connecterUtilisateur  = function(utilisateur, surSucces, surErreur) {	
		var url_service = config.url_service_annuaire.replace("{service}", "connexion");
		donnees_post = {courriel : utilisateur.courriel, mdp : utilisateur.mdp, persistance : true, methode : "connexion"};
		// Besoin d'un objet particulier ici car sinon angular poste du json
		$http({
		    method: 'PUT',
		    url: url_service,
		    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		    transformRequest: function(obj) {
		        var str = [];
		        for(var p in obj)
		        str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
		        return str.join("&");
		    },
		    data: donnees_post
		}).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur();
		});
	};
	
	etatApplicationService.connaitreEtatUtilisateur = function(surSucces, surErreur) {	
		var url_service = config.url_service_annuaire.replace("{service}", "identite-connectee");
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
		$http.delete(url_service).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	return etatApplicationService;	
});