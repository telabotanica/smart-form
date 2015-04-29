smartFormApp.service('smartFormService', function($http) {
	
	var smartFormService = {};
	
	/** FICHES **/ 
	smartFormService.getListeFichesSmartFlore = function(recherche, utilisateur, pageCourante, taillePage, surSucces, surErreur) {
		
		var referentiel = "referentiel="+(!recherche.referentiel ? '%' : recherche.referentiel);
		var rechercheLibre = "&recherche="+(!recherche.texte ? '%' : recherche.texte);
		var pagesExistantes = '&pages_existantes='+(!!recherche.fichesExistantes);
		var pagination = '&debut='+(pageCourante*taillePage)+"&limite="+taillePage;
		var utilisateurConnecte = (utilisateur.connecte && utilisateur.nomWiki != '') ? '&utilisateur='+utilisateur.nomWiki : '';
		
		$http.get(config.url_service_pages+'?'+referentiel+rechercheLibre+pagesExistantes+pagination+utilisateurConnecte).
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
			surEchec();
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
			surEchec();
		});
	};
	
	/** SENTIERS **/
	smartFormService.getSentiers = function(utilisateur, surSucces, surErreur) {
		var utilisateur = "utilisateur="+utilisateur;
		
		$http.get(config.url_service_sentiers+'/sentier/'+'?'+utilisateur).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	smartFormService.ajouterSentier = function(utilisateur, sentierTitre, surSucces, surErreur) {
		donnees_post = {"utilisateur" : utilisateur, "sentierTitre" : sentierTitre};
		$http.put(config.url_service_sentiers+'/sentier/', donnees_post).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surEchec();
		});
	};
	
	
	
	return smartFormService;	
});