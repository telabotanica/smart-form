smartFormApp.controller('ListeControleur', function ($scope, $rootScope, smartFormService, wikiniService, paginationService, etatApplicationService) {
	
	this.fiches = [];
		
	var lthis = this;
	$scope.$on('pagination.page-changee', function() {
		lthis.getFiches();
	});
	$scope.$on('recherche.recherche-effectuee', function() {
		lthis.getFiches();
	});

	this.getFiches = function() {
		
		$rootScope.$broadcast('masque.start-chargement');
		
		if(etatApplicationService.rechercheModifiee && !etatApplicationService.premierChargement) {
			// Remise au début de l'état de la pagination (car on a changé de recherche)
			$rootScope.$broadcast('pagination.reset-pagination');
			etatApplicationService.rechercheModifiee = false;
		}
		
		var lthis = this;
		smartFormService.getListeFichesSmartFlore(etatApplicationService.recherche, paginationService.pageCourante, paginationService.taillePage,
		function(data) {
				lthis.fiches = data.resultats;
				$rootScope.$broadcast('pagination.construire-pagination', data.pagination);
				$rootScope.$broadcast('masque.stop-chargement');	
			}, 
			function(data) {
				$rootScope.$broadcast('masque.stop-chargement');
			}
		);
	};
	
	this.editerFiche = function(fiche) {
		$rootScope.$broadcast('edition.editer-fiche', fiche);
	};
	
	this.afficherFicheMobile = function(fiche) {
		referentiel_fiche = ""+fiche.infos_taxon.referentiel;
		url_fiche = this.config.url_fiche_mobile.replace('{referentiel}', referentiel_fiche.toLowerCase());
		url_fiche = url_fiche.replace('{num_nom}', fiche.infos_taxon.num_nom);
		window.open(url_fiche);
	};
	
	this.afficherQrCode = function(fiche) {
		url = config.url_qr_code.replace('{referentiel}', fiche.infos_taxon.referentiel.toLowerCase());
		url = url.replace('{num_nom}', fiche.infos_taxon.num_nom);
		window.open(url);
	};
	
	this.getFiches();
});