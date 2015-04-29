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
	
	this.editerFiche = function(fiche) {
		$rootScope.$broadcast('edition.editer-fiche', fiche);
	};
	
	this.getFavoris = function() {
		var lthis = this;
		smartFormService.getListeFichesFavorites(etatApplicationService.utilisateur.nomWiki,
		function(data) {
			lthis.fiches = data.resultats;
		}, 
		function() {
			
		});
	};
	
	this.ajouterFavoris = function(fiche) {
		var lthis = this;
		smartFormService.ajouterFicheFavorite(etatApplicationService.utilisateur.nomWiki, fiche.tag,
		function(data) {
			if(data == 'OK') {
				lthis.ajouterOuMettreAJourFavoris(fiche);
			}
		}, 
		function() {
			console.log('C\'est pas bon !');
		});
	};
	
	this.supprimerFavoris = function(fiche) {
		var lthis = this;
		smartFormService.supprimerFicheFavorite(etatApplicationService.utilisateur.nomWiki, fiche.tag,
		function(data) {
			if(data == 'OK') {
				$rootScope.$broadcast('favoris.fiche-supprimee', fiche);
				lthis.supprimerFicheDeLaListe(fiche);
			}
		}, 
		function() {
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