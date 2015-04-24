smartFormApp.controller('SmartFormControleur', function ($scope, $http, $controller, wikiniService, smartFormService) {
	
	// globale provenant de config.js
	$scope.config = config;
	
	// état dictant la vue active dans la page
	$scope.etat = "liste";
	
	// affiche ou cache le masque de chargement
	$scope.chargement = true;
	
	$scope.fiches = [];
	
	$scope.recherche = {};
	$scope.recherche.texte = "";
	$scope.referentiels = config.referentiels;
	$scope.recherche.referentiel = config.referentiels[0];
	$scope.rechercheModifiee = false;
	$scope.premierChargement = true;
	
	// service d'accès et modification aux pages wikini
	$scope.wikiniService = wikiniService;
			
	this.initApplication = function() {
		
		this.initPaginationCtrl();
		this.initEditionControleur();
		
		$scope.$watch('recherche', function(newVal, oldVal) {
			if(!$scope.premierChargement) {
				$scope.rechercheModifiee = true;
			}
			$scope.premierChargement = false;
		}, true);
				
		$scope.$on('stop-chargement', function() {
			$scope.chargement = false;
		});
		
		$scope.getFiches();
	};
	
	this.initPaginationCtrl = function() {
		$scope.paginationCtrl = $controller("PaginationControleur");
		// instanciation de fonction "abstraite"
		$scope.paginationCtrl.surChangementPage = function() {
			$scope.getFiches();
		};
		$scope.paginationCtrl.nomElementTrouve = "fiches";
		$scope.paginationCtrl.nomElementTrouveSingulier = "fiche trouvée"; 
		$scope.paginationCtrl.nomElementTrouvePluriel = "fiches trouvées";
	};
	
	this.initEditionControleur = function() {
		$scope.editionCtrl = $controller("EditionControleur");
	};
	
	$scope.getFiches = function() {

		$scope.chargement = true;

		if($scope.rechercheModifiee && !$scope.premierChargement) {
			$scope.paginationCtrl.resetPagination();
			$scope.rechercheModifiee = false;
		}
		
		smartFormService.getListeFichesSmartFlore($scope.recherche, $scope.paginationCtrl.pageCourante, $scope.paginationCtrl.taillePage, 
			function(data) {
				$scope.paginationCtrl.construireNbPages(data.pagination);
				$scope.paginationCtrl.afficherPagination = data.resultats.length > 0;
				$scope.fiches = data.resultats;
				$scope.chargement = false;
			}, 
			function(data) {
				$scope.chargement = false;
			}
		);
	};
	
	$scope.afficherQrCode = function(fiche) {
		url = config.url_qr_code.replace('{referentiel}', fiche.infos_taxon.referentiel.toLowerCase());
		url = url.replace('{num_nom}', fiche.infos_taxon.num_nom);
		window.open(url);
	};
	
	$scope.changerEtat = function(etat) {
		$scope.etat = etat;
	};
	
	$scope.editerFiche = function(fiche) {
		// charger les données de fiche
		$scope.etat = "edition";
		$scope.editionCtrl.editerFiche(fiche);
	};
	
	$scope.afficherFicheMobile = function(fiche) {
		referentiel_fiche = ""+fiche.infos_taxon.referentiel;
		url_fiche = $scope.config.url_fiche_mobile.replace('{referentiel}', referentiel_fiche.toLowerCase());
		url_fiche = url_fiche.replace('{num_nom}', fiche.infos_taxon.num_nom);
		window.open(url_fiche);
	};
		
	// Initialisation du controleur
	this.initApplication();
});