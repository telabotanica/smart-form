smartFormApp.controller('ListeControleur', function ($scope, $rootScope, smartFormService, wikiniService, paginationService, etatApplicationService, liensService) {
	
	this.fiches = [];	
	this.afficherFonctionsUtilisateurIdentifie = etatApplicationService.utilisateur.connecte;
	
	this.liensService = liensService;
		
	var lthis = this;
	$scope.$on('pagination.page-changee', function() {
		lthis.getFiches();
	});
	$scope.$on('recherche.recherche-effectuee', function() {
		lthis.getFiches();
	});
	
	$scope.$on('favoris.fiche-supprimee', function(event, fiche) {
	    var i;
	    for (i = 0; i < lthis.fiches.length; i++) {
	        if (lthis.fiches[i].tag === fiche.tag) {
	        	lthis.fiches[i].favoris = false;
	        	return;
	        }
	    }
	});
	
	$scope.$on('utilisateur.utilisateur-connecte', function(event, utilisateur) {
		lthis.afficherFonctionsUtilisateurIdentifie = utilisateur.connecte;
		lthis.getFiches();
	});
	
	$scope.$on('utilisateur.utilisateur-deconnecte', function(event, utilisateur) {
		lthis.afficherFonctionsUtilisateurIdentifie = false;
	});
	
	$scope.$on('edition.fiche-editee', function(event, fiche) {
	    var i;
	    for (i = 0; i < lthis.fiches.length; i++) {
	        if (lthis.fiches[i].tag === fiche.tag) {
	        	lthis.fiches[i].existe = true;
	        	lthis.fiches[i].nb_revisions = parseInt(lthis.fiches[i].nb_revisions) + 1;
	        	return;
	        }
	    }
	});

	this.getFiches = function() {
		
		$rootScope.$broadcast('masque.start-chargement');
		
		if(etatApplicationService.rechercheModifiee && !etatApplicationService.premierChargement) {
			// Remise au début de l'état de la pagination (car on a changé de recherche)
			$rootScope.$broadcast('pagination.reset-pagination');
			etatApplicationService.rechercheModifiee = false;
		}
		
		var lthis = this;
		smartFormService.getListeFichesSmartFlore(etatApplicationService.recherche, etatApplicationService.utilisateur, paginationService.pageCourante, paginationService.taillePage,
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
		if(!fiche.favoris) {
			$rootScope.$broadcast('favoris.ajouter-fiche', fiche);
		} else {
			$rootScope.$broadcast('favoris.supprimer-fiche', fiche);
		}
		fiche.favoris = !fiche.favoris;
	};
	
	this.getFiches();
});