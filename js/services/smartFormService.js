smartFormApp.service('smartFormService', function($http, etatApplicationService) {
	
	var smartFormService = {};
	
	/** FICHES **/ 
	smartFormService.getListeFichesSmartFlore = function(recherche, utilisateur, pageCourante, taillePage, surSucces, surErreur) {
		
		var referentiel = "referentiel="+(!recherche.referentiel ? '%' : recherche.referentiel);
		var referentielVerna = '&referentiel_verna='+(recherche.referentielVerna);
		var rechercheLibre = "&recherche="+(!recherche.texte ? '%' : recherche.texte);
		var pagesExistantes = '&pages_existantes='+(!!recherche.fichesExistantes);
		var nomsVernaculaires = '&nom_verna='+(!!recherche.nomVernaculaire);
		var pagination = '&debut='+(pageCourante*taillePage)+"&limite="+taillePage;
		var utilisateurConnecte = (utilisateur.connecte && utilisateur.nomWiki != '') ? '&utilisateur='+utilisateur.nomWiki : '';
		
		$http.get(config.url_service_pages+'?'+referentiel+referentielVerna+rechercheLibre+pagesExistantes+nomsVernaculaires+pagination+utilisateurConnecte).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	 
	smartFormService.getListeFichesSmartFloreAsync = function(recherche, pageCourante, taillePage, callback) {
		
		var referentiel = "referentiel="+(!recherche.referentiel ? '%' : recherche.referentiel);
		var referentielVerna = '&referentiel_verna='+(recherche.referentielVerna);
		var rechercheLibre = "&recherche="+(!recherche.texte ? '%' : recherche.texte);
		var pagesExistantes = '&pages_existantes='+(!!recherche.fichesExistantes);
		var nomsVernaculaires = '&nom_verna='+(!!recherche.nomVernaculaire);
		var pagination = '&debut='+(pageCourante*taillePage)+"&limite="+taillePage;
		// Afin de ne renvoyer qu'une simple liste de noms
		var retour = '&retour=min';
		
		return $http.get(config.url_service_pages+'?'+referentiel+referentielVerna+rechercheLibre+pagesExistantes+nomsVernaculaires+pagination+retour)
		.then(function(retour) {
			var resultatsFmt = [];
			var possibilites = retour.data.resultats;
			var nbRes = possibilites.length;
			for ( var i = 0; i < nbRes; i++) {				
				// Afin d'éviter les doublons (cas fréquent pour les noms vernaculaires)
				if(possibilites[i] != "" && resultatsFmt.indexOf(possibilites[i]) == -1) {
					resultatsFmt.push(possibilites[i]);
				} 
			}
			return resultatsFmt;
		});
	};
	
	/** FICHES **/ 
	smartFormService.getFicheSmartFlore = function(referentiel, numTax, surSucces, surErreur) {
		
		var paramReferentiel = "referentiel="+referentiel;
		var paramNumTax = "&num_tax="+numTax;
		var paramUn = "&retour=un";
		
		$http.get(config.url_service_pages+'?'+paramReferentiel+paramNumTax+paramUn).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	
	/** FAVORIS **/ 
	smartFormService.getListeFichesFavorites = function(surSucces, surErreur) {

		$http({
		    method: 'GET',
		    url: config.url_service_favoris,
		    headers: {
		    	'Authorization': etatApplicationService.jeton
		    }
		}).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	smartFormService.ajouterFicheFavorite = function(pageTag, surSucces, surErreur) {
		donnees_post = {
			"pageTag" : pageTag
		};
		$http({
		    method: 'PUT',
		    url: config.url_service_favoris,
		    headers: {
		    	'Authorization': etatApplicationService.jeton
		    },
		    data: donnees_post
		}).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	smartFormService.supprimerFicheFavorite = function(pageTag, surSucces, surErreur) {
		donnees_post = {
			"pageTag" : pageTag
		};
		// Attention lors d'un delete les données supplémentaires doivent être encapsulé dans la propriété
		// data de l'objet à envoyer (pourquoi ? je ne sais pas)
		$http({
		    method: 'DELETE',
		    url: config.url_service_favoris,
		    headers: {
		    	'Authorization': etatApplicationService.jeton
		    },
		    data : donnees_post
		}).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	/** SENTIERS **/
	smartFormService.getSentiers = function(voirTousLesSentiers, surSucces, surErreur) {
		$http({
		    method: 'GET',
		    url: config.url_service_sentiers + '/sentier/',
		    headers: (! voirTousLesSentiers) ? {'Authorization': etatApplicationService.jeton} : {}
		}).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	smartFormService.ajouterSentier = function(sentierTitre, surSucces, surErreur) {
		var donnees_post = {
			"sentierTitre" : sentierTitre
		};
		$http({
		    method: 'PUT',
		    url: config.url_service_sentiers + '/sentier/',
		    headers: {
		    	'Authorization': etatApplicationService.jeton
		    },
		    data: donnees_post
		}).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	smartFormService.supprimerSentier = function(sentierTitre, surSucces, surErreur) {
		var donnees_post = {
			"sentierTitre" : sentierTitre
		};
		// Attention lors d'un delete les données supplémentaires doivent être encapsulé dans la propriété
		// data de l'objet à envoyer (pourquoi ? je ne sais pas)
		$http({
		    method: 'DELETE',
		    url: config.url_service_sentiers + '/sentier/',
		    headers: {
		    	'Authorization': etatApplicationService.jeton
		    },
		    data: donnees_post
		}).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	smartFormService.getFichesASentier = function(sentierTitre, surSucces, surErreur) {
		var sentier = "sentierTitre="+sentierTitre;
		$http.get(config.url_service_sentiers+'/sentier-fiche/?'+sentier).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	smartFormService.ajouterFicheASentier = function(sentierTitre, pageTag, surSucces, surErreur) {
		donnees_post = {
			"sentierTitre" : sentierTitre, "pageTag" : pageTag
		};
		$http({
		    method: 'PUT',
		    url: config.url_service_sentiers + '/sentier-fiche/',
		    headers: {
		    	'Authorization': etatApplicationService.jeton
		    },
		    data: donnees_post
		}).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	smartFormService.supprimerFicheASentier = function(sentierTitre, pageTag, surSucces, surErreur) {
		var donnees_post = {
			"sentierTitre" : sentierTitre, "pageTag" : pageTag
		};
		// Attention lors d'un delete les données supplémentaires doivent être encapsulé dans la propriété
		// data de l'objet à envoyer (pourquoi ? je ne sais pas)
		$http({
		    method: 'DELETE',
		    url: config.url_service_sentiers + '/sentier/',
		    headers: {
		    	'Authorization': etatApplicationService.jeton
		    },
		    data: donnees_post
		}).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	return smartFormService;	
});