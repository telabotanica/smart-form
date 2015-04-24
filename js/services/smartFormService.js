smartFormApp.service('smartFormService', function($http) {
	
	var smartFormService = {};
	
	smartFormService.getListeFichesSmartFlore = function(recherche, pageCourante, taillePage, surSucces, surErreur) {
		
		var referentiel = "referentiel="+(!recherche.referentiel ? '%' : recherche.referentiel);
		var rechercheLibre = "&recherche="+(!recherche.texte ? '%' : recherche.texte);
		var pagesExistantes = '&pages_existantes='+(!!recherche.fichesExistantes);
		var pagination = '&debut='+(pageCourante*taillePage)+"&limite="+taillePage;
		
		$http.get(config.url_service+'?'+referentiel+rechercheLibre+pagesExistantes+pagination).
		success(function(data, status, headers, config) {
			surSucces(data);
		}).
		error(function(data, status, headers, config) {
			surErreur(data);
		});
	};
	
	return smartFormService;	
});