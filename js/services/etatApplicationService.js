smartFormApp.service('etatApplicationService', function() {
	
	var etatApplicationService = {};
	etatApplicationService.recherche = {};
	etatApplicationService.recherche.texte = "";
	etatApplicationService.referentiels = config.referentiels;
	etatApplicationService.recherche.referentiel = config.referentiels[0];
	etatApplicationService.recherche.fichesExistantes = false;
	etatApplicationService.rechercheModifiee = false;
	etatApplicationService.premierChargement = true;
	
	return etatApplicationService;	
});