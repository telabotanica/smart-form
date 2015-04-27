smartFormApp.controller('SmartFormControleur', function ($scope, $rootScope, paginationService) {
	
	// globale provenant de config.js
	$scope.config = config;
	
	// état dictant la vue active dans la page
	$scope.etat = "liste";
	
	// affiche ou cache le masque de chargement
	$scope.chargement = true;
				
	this.initApplication = function() {
		this.configPagination();
		
		$scope.$on('masque.start-chargement', function() {
			$scope.chargement = true;
		});
		
		$scope.$on('masque.stop-chargement', function() {
			$scope.chargement = false;
		});
		
		$scope.$on('etat.changement-etat', function(event, etat) {
			// Permet de déclencher l'affichage de la liste ou du formulaire
			// Suivant l'état (quand on sera grand on utilisera des "routes")
			$scope.changerEtat(etat);
		});
		
		$rootScope.$broadcast('liste.afficher-liste');
	};
		
	$scope.changerEtat = function(etat) {
		$scope.etat = etat;
	};
		
	this.configPagination = function() {
		// configuration des noms d'éléments affichés par la pagination
		paginationService.nomElementTrouve = "fiches";
		paginationService.nomElementTrouveSingulier = "fiche trouvée"; 
		paginationService.nomElementTrouvePluriel = "fiches trouvées";
	};
		
	// Initialisation du controleur
	this.initApplication();
});