smartFormApp.controller('ListeControleur', function ($scope, $rootScope, smartFormService, wikiniService, paginationService, etatApplicationService, liensService) {
	
	this.fiches = [];
	this.afficherFavoris = etatApplicationService.utilisateur.connecte;
	
	this.liensService = liensService;
		
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
	
	this.ajouterFavoris = function(fiche) {
		$rootScope.$broadcast('favoris.ajouter-fiche', fiche);
	};
	
	this.getFiches();
});