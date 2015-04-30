smartFormApp.controller('SentiersControleur', function ($scope, $rootScope, smartFormService, etatApplicationService, liensService) {
	
	this.sentiers = [];
	this.sentierSelectionne = {};
	this.sentierSelectionne.titre = "";
	this.sentierSelectionne.fiches = [];
	
	this.nouveauSentierTitre = "";
	
	this.afficherSentiers = etatApplicationService.utilisateur.connecte;
	this.utilisateurNomWiki = etatApplicationService.utilisateur.nomWiki;
	
	this.liensService = liensService;
	
	var lthis = this;
	$scope.$on('utilisateur.utilisateur-connecte', function(event, utilisateur) {
		lthis.afficherSentiers = true;
		lthis.utilisateurNomWiki = utilisateur.nomWiki;
	});
	
	$scope.$on('utilisateur.utilisateur-deconnecte', function(event) {
		lthis.afficherSentiers = false;
		lthis.utilisateurNomWiki = "";
	});
	
	$scope.$on('dropEvent', function(evt, dragged, dropped) {
		lthis.ajouterFicheASentier(lthis.sentierSelectionne, dragged);
	});
	
	this.editerFiche = function(fiche) {
		$rootScope.$broadcast('edition.editer-fiche', fiche);
	};
	
	this.surChangementSentier = function() {
		smartFormService.getFichesASentier(this.sentierSelectionne.titre,
		function(data) {
			lthis.sentierSelectionne.fiches = data.resultats;
		}, 
		function() {
			
		});
	};
	
	this.getSentiers = function() {
		var lthis = this;
		smartFormService.getSentiers(etatApplicationService.utilisateur.nomWiki, etatApplicationService.voirTousLesSentiers, 
		function(data) {
			lthis.sentiers = data.resultats;
			if(lthis.sentiers.length > 0) {
				lthis.sentierSelectionne = lthis.sentiers[0];
				lthis.surChangementSentier();
			}
		}, 
		function() {
			
		});
	};
	
	this.ajouterSentier = function() {
		var lthis = this;
		if(this.verifierValiditeSentier()) {
			smartFormService.ajouterSentier(etatApplicationService.utilisateur.nomWiki, this.nouveauSentierTitre,
			function(data) {
				if(data == 'OK') {
					lthis.initialiserNouveauSentier(lthis.nouveauSentierTitre);
					lthis.nouveauSentierTitre = "";
				}
			}, 
			function() {
				console.log('C\'est pas bon !');
			});
		} else {
			window.alert("Le nom du sentier n'est pas valide, vérifiez que n'avez pas saisi un nom qui existe déjà.");
		}
	};
	
	this.supprimerSentier = function(sentier) {
		var lthis = this;
		if(window.confirm("Êtes-vous sûr de vouloir supprimer ce sentier ?")) {
			smartFormService.supprimerSentier(etatApplicationService.utilisateur.nomWiki, sentier.titre,
			function(data) {
				if(data == 'OK') {
					lthis.supprimerSentierDeLaListe(sentier);
					if(lthis.sentiers.length > 0) {
						lthis.sentierSelectionne = lthis.sentiers[lthis.sentiers.length - 1];
					} else {
						lthis.sentierSelectionne = {};
						lthis.sentierSelectionne.titre = "";
						lthis.sentierSelectionne.fiches = [];
					}
				}
			}, 
			function() {
				console.log('C\'est pas bon !');
			});
		}
	};
	
	this.ajouterFicheASentier = function(sentier, fiche) {
		var lthis = this;
		if(!lthis.sentierSelectionneContientFiche(fiche.tag)) {
			smartFormService.ajouterFicheASentier(etatApplicationService.utilisateur.nomWiki, sentier.titre, fiche.tag,
			function(data) {
				if(data == 'OK') {
					lthis.sentierSelectionne.fiches.push(fiche);
					// nécéssaire pour mettre à jour l'affichage à cause du contexte inhabituel
					// dans lequel est appelée la fonction (directive drag and drop)
					//$scope.$apply();
				}
			}, 
			function() {
				console.log('C\'est pas bon !');
			});
		}
	};
	
	this.supprimerFicheASentier = function(sentier, fiche) {
		var lthis = this;
		if(window.confirm("Êtes-vous sûr de vouloir supprimer cette fiche du sentier ?")) {
			smartFormService.supprimerFicheASentier(sentier.titre, fiche.tag,
			function(data) {
				if(data == 'OK') {
					lthis.supprimerFicheDuSentier(sentier, fiche);
				}
			}, 
			function() {
				console.log('C\'est pas bon !');
			});
		}
	};
	
	this.initialiserNouveauSentier = function(titre) {
		var nouveauSentier = {};
		nouveauSentier.titre = titre;
		nouveauSentier.auteur = etatApplicationService.utilisateur.nomWiki;
		nouveauSentier.fiches = [];
		this.sentiers.push(nouveauSentier);
		this.sentierSelectionne = this.sentiers[this.sentiers.length - 1];
	};
	
	this.verifierValiditeSentier = function() {
		return !!this.nouveauSentierTitre && !this.contientSentier(this.nouveauSentierTitre);
	};
	
	this.contientSentier = function(sentierTitre) {
	    var i;
	    for (i = 0; i < this.sentiers.length; i++) {
	        if (this.sentiers[i].titre === sentierTitre) {
	        	return true;
	        }
	    }
	    return false;
	};
	
	this.sentierSelectionneContientFiche = function(ficheTitre) {
	    var i;
	    for (i = 0; i < this.sentierSelectionne.fiches.length; i++) {
	        if (this.sentierSelectionne.fiches[i].tag === ficheTitre) {
	        	return true;
	        }
	    }
	    return false;
	};
	
	this.supprimerSentierDeLaListe = function(sentier) {
	    var i;
	    for (i = 0; i < this.sentiers.length; i++) {
	    	 if (this.sentiers[i].titre === sentier.titre) {
	        	this.sentiers.splice(i, 1);
	        }
	    }
	    return false;
	};
	
	this.supprimerFicheDuSentier = function(sentier, fiche) {
	    var i;
	    for (i = 0; i < sentier.fiches.length; i++) {
	    	 if (sentier.fiches[i].tag === fiche.tag) {
	    		 sentier.fiches.splice(i, 1);
	        }
	    }
	    return false;
	};
	
	this.surChangementSaisieSentier = function() {
		//TODO: Avertir l'utilisateur en cas de saisie d'un sentier déjà existant ?
	};	
	
	this.getSentiers();
});