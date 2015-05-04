smartFormApp.service('paginationService', function() {
	
	var paginationService = {};
	paginationService.nbPages = 0;
	paginationService.totalResultats = 0;
	paginationService.taillePage = 10;
	paginationService.pageCourante = 0; 
	
	paginationService.nomElementTrouve = "éléments";
	paginationService.nomElementTrouveSingulier = "élément trouvé"; 
	paginationService.nomElementTrouvePluriel = "éléments trouvés"; 
	
	return paginationService;	
});