smartFormApp.controller('FavorisControleur', function ($scope, $rootScope, smartFormService, etatApplicationService, liensService) {
	
	this.fiches = [];
	this.afficherFavoris = etatApplicationService.utilisateur.connecte;
	
	this.liensService = liensService;
	
	var lthis = this;
	$scope.$on('favoris.ajouter-fiche', function(event, fiche) {
		lthis.ajouterFavoris(fiche);
	});
	$scope.$on('favoris.supprimer-fiche', function(event, fiche) {
		lthis.supprimerFavoris(fiche);
	});
	
	$scope.$on('utilisateur.utilisateur-connecte', function(event, utilisateur) {
		lthis.afficherFavoris = utilisateur.connecte;
		lthis.getFavoris();
	});
	
	$scope.$on('utilisateur.utilisateur-deconnecte', function(event) {
		lthis.afficherFavoris = false;
	});
	
	$scope.$on('edition.fiche-editee', function(event, fiche) {
	    var i;
	    for (i = 0; i < lthis.fiches.length; i++) {
	        if (lthis.fiches[i].tag === fiche.tag) {
	        	lthis.fiches[i].existe = true;
	        	lthis.fiches[i].nb_revisions += 1;
	        	return;
	        }
	    }
	});
	
	this.editerFiche = function(fiche) {
		$rootScope.$broadcast('edition.editer-fiche', fiche);
	};
	
	this.getFavoris = function() {
		var lthis = this;
		smartFormService.getListeFichesFavorites(
		function(data) {
			lthis.fiches = data.resultats;
		}, 
		function(data) {
			
		});
	};
	
	this.ajouterFavoris = function(fiche) {
		var lthis = this;
		smartFormService.ajouterFicheFavorite(fiche.tag,
		function(data) {
			if(data == 'OK') {
				lthis.ajouterOuMettreAJourFavoris(fiche);
			}
		}, 
		function(data) {
			console.log('C\'est pas bon !');
		});
	};
	
	this.supprimerFavoris = function(fiche) {
		var lthis = this;
		smartFormService.supprimerFicheFavorite(fiche.tag,
		function(data) {
			if(data == 'OK') {
				$rootScope.$broadcast('favoris.fiche-supprimee', fiche);
				lthis.supprimerFicheDeLaListe(fiche);
			}
		}, 
		function(data) {
			console.log('C\'est pas bon !');
		});
	};
	
	this.ajouterOuMettreAJourFavoris = function(fiche) {
		// Vérification si la fiche n'est pas déjà présente 
		// TODO: voir si c'est utile
	    var i;
	    for (i = 0; i < this.fiches.length; i++) {
	        if (this.fiches[i].tag === fiche.tag) {
	        	return;
	        }
	    }
	    this.fiches[i] = fiche;
	};
	
	this.supprimerFicheDeLaListe = function(fiche) {
	    var i;
	    for (i = 0; i < this.fiches.length; i++) {
	        if (this.fiches[i].tag === fiche.tag) {
	        	this.fiches.splice(i, 1);
	        }
	    }
	};
	
	if(this.afficherFavoris) {
		this.getFavoris();
	}
});