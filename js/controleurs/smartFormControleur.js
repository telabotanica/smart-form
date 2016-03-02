smartFormApp.controller('SmartFormControleur', function ($scope, $rootScope, paginationService, etatApplicationService, googleAnalyticsService) {

	// globale provenant de config.js
	$scope.config = config;

	// état dictant la vue active dans la page
	$scope.etat = "liste";

	// nom de l'application d'après la config
	$scope.nom_application = config.nom_application;

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

		// Stats GA
		googleAnalyticsService.init();
		googleAnalyticsService.envoyerPageVue();

		var lthis = this;
		setTimeout(function(){ lthis.analyserEtatApplication(); }, 200);
	};

	$scope.changerEtat = function(etat) {
		$scope.etat = etat;
	};

	this.analyserEtatApplication = function() {
		if(!!etatApplicationService.queryString) {
			var action = etatApplicationService.queryString.action;
			if(!!action && action == 'editer-fiche') {
				var infos = {};
				infos.num_tax = etatApplicationService.queryString.num_tax;
				infos.referentiel = etatApplicationService.queryString.referentiel;
				$rootScope.$broadcast('edition.charger-editer-fiche', infos);
			} else {
				$rootScope.$broadcast('liste.afficher-liste');
			}
		} else {
			$rootScope.$broadcast('liste.afficher-liste');
		}
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
