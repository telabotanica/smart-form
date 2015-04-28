smartFormApp.service('smartFormService', function($http) {
	
	var smartFormService = {};
	
	smartFormService.getListeFichesSmartFlore = function(recherche, pageCourante, taillePage, surSucces, surErreur) {
		
		var referentiel = "referentiel="+(!recherche.referentiel ? '%' : recherche.referentiel);
		var rechercheLibre = "&recherche="+(!recherche.texte ? '%' : recherche.texte);
		var pagesExistantes = '&pages_existantes='+(!!recherche.fichesExistantes);
		var pagination = '&debut='+(pageCourante*taillePage)+"&limite="+taillePage;
		
		$http.get(config.url_service_pages+'?'+referentiel+rechercheLibre+pagesExistantes+pagination).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
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
		$http.delete(config.url_service_favoris, {data : donnees_post}).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surEchec();
		});
	};
	
	return smartFormService;	
});