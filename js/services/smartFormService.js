smartFormApp.service('smartFormService', function($http) {
	
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
	smartFormService.getListeFichesFavorites = function(utilisateur, surSucces, surErreur) {
		var utilisateur = "utilisateur="+utilisateur;
		
		$http.get(config.url_service_favoris+'?'+utilisateur).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	smartFormService.ajouterFicheFavorite = function(utilisateur, pageTag, surSucces, surErreur) {
		donnees_post = {"utilisateur" : utilisateur, "pageTag" : pageTag};
		$http.put(config.url_service_favoris, donnees_post).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	smartFormService.supprimerFicheFavorite = function(utilisateur, pageTag, surSucces, surErreur) {
		donnees_post = {"utilisateur" : utilisateur, "pageTag" : pageTag};
		// Attention lors d'un delete les données supplémentaires doivent être encapsulé dans la propriété
		// data de l'objet à envoyer (pourquoi ? je ne sais pas)
		$http.delete(config.url_service_favoris, {data : donnees_post}).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	/** SENTIERS **/
	smartFormService.getSentiers = function(utilisateur, voirTousLesSentiers, surSucces, surErreur) {
		var params = "";
		if(!voirTousLesSentiers) {
			params = "?utilisateur="+utilisateur;
		}
		
		$http.get(config.url_service_sentiers+'/sentier/'+params).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	smartFormService.ajouterSentier = function(utilisateur, utilisateurCourriel, sentierTitre, surSucces, surErreur) {
		// le courriel est nécéssaire pour des fonctions de suivi
		// TODO : quand on aura un sso bien implémenté on devrait pouvoir s'en passer
		var donnees_post = {"utilisateur" : utilisateur, "sentierTitre" : sentierTitre, "utilisateurCourriel" : utilisateurCourriel};
		$http.put(config.url_service_sentiers+'/sentier/', donnees_post).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	smartFormService.supprimerSentier = function(utilisateur, sentierTitre, surSucces, surErreur) {
		var donnees_post = {"utilisateur" : utilisateur, "sentierTitre" : sentierTitre};
		// Attention lors d'un delete les données supplémentaires doivent être encapsulé dans la propriété
		// data de l'objet à envoyer (pourquoi ? je ne sais pas)
		$http.delete(config.url_service_sentiers+'/sentier/', {data : donnees_post}).
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
	
	smartFormService.ajouterFicheASentier = function(utilisateur, sentierTitre, pageTag, surSucces, surErreur) {
		donnees_post = {"utilisateur" : utilisateur, "sentierTitre" : sentierTitre, "pageTag" : pageTag};
		$http.put(config.url_service_sentiers+'/sentier-fiche/', donnees_post).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	smartFormService.supprimerFicheASentier = function(sentierTitre, pageTag, surSucces, surErreur) {
		var donnees_post = {"sentierTitre" : sentierTitre, "pageTag" : pageTag};
		// Attention lors d'un delete les données supplémentaires doivent être encapsulé dans la propriété
		// data de l'objet à envoyer (pourquoi ? je ne sais pas)
		$http.delete(config.url_service_sentiers+'/sentier-fiche/', {data : donnees_post}).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	return smartFormService;	
});