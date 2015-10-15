smartFormApp.controller('SmartFormControleur', function ($scope, $rootScope, paginationService, etatApplicationService) {
	
	// globale provenant de config.js
	$scope.config = config;
	
	// état dictant la vue active dans la page
	$scope.etat = "liste";

	// nom de l'application d'après la config
	$scope.nom_application = config.nom_application;

	// est-on en prod ou en test ?
	$scope.prod = config.prod;
	
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

		// chargement de Google Analytics uniquement si on est en prod
		if ($scope.prod) {
			(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

			ga('create', 'UA-57885-5', 'auto');
			ga('send', 'pageview');
		}
		
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