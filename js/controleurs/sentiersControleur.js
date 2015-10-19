smartFormApp.controller('SentiersControleur', function ($scope, $rootScope, smartFormService, etatApplicationService, liensService, googleAnalyticsService) {
	
	this.sentiers = [];
	this.sentierSelectionne = {};
	this.sentierSelectionne.titre = "";
	this.sentierSelectionne.fiches = [];
	
	this.nouveauSentierTitre = "";
	
	this.afficherSentiers = etatApplicationService.utilisateur.connecte;
	this.utilisateurNomWiki = etatApplicationService.utilisateur.nomWiki;
	
	this.liensService = liensService;
	this.chargementSentier = false;
	
	var lthis = this;
	$scope.$on('utilisateur.utilisateur-connecte', function(event, utilisateur) {
		lthis.utilisateurNomWiki = utilisateur.nomWiki;
		lthis.afficherSentiers = utilisateur.connecte;
		lthis.getSentiers();
	});
	
	$scope.$on('utilisateur.utilisateur-deconnecte', function(event) {
		lthis.afficherSentiers = false;
		lthis.utilisateurNomWiki = "";
	});
	
	$scope.$on('dropEvent', function(evt, dragged, dropped) {
		lthis.ajouterFicheASentier(lthis.sentierSelectionne, dragged);
	});
	
	$scope.$on('edition.fiche-editee', function(event, fiche) {
	    var i;
	    for (i = 0; i < lthis.sentierSelectionne.fiches.length; i++) {
	        if (lthis.sentierSelectionne.fiches[i].tag === fiche.tag) {
	        	lthis.sentierSelectionne.fiches[i].existe = true;
	        	lthis.sentierSelectionne.fiches[i].nb_revisions = parseInt(lthis.sentierSelectionne.fiches[i].nb_revisions) + 1;
	        	return;
	        }
	    }
	});
	
	this.editerFiche = function(fiche) {
		$rootScope.$broadcast('edition.editer-fiche', fiche);
	};
	
	this.surChangementSentier = function() {
		this.chargementSentier = true;
		smartFormService.getFichesASentier(this.sentierSelectionne.titre,
		function(data) {
			lthis.sentierSelectionne.fiches = data.resultats;
			lthis.chargementSentier = false;
		}, 
		function(data) {
			
		});
	};
	
	this.getSentiers = function() {
		var lthis = this;
		smartFormService.getSentiers(etatApplicationService.voirTousLesSentiers, 
		function(data) {
			lthis.sentiers = data.resultats;
			if(lthis.sentiers.length > 0) {
				lthis.sentierSelectionne = lthis.sentiers[0];
				lthis.surChangementSentier();
			}
			lthis.afficherSentiers = etatApplicationService.utilisateur.connecte;
		}, 
		function(data) {
			
		});
	};
	
	this.ajouterSentier = function() {
		var lthis = this;
		if(this.verifierValiditeSentier()) {
			smartFormService.ajouterSentier(this.nouveauSentierTitre,
			function(data) {
				if(data == 'OK') {
					// stats
					googleAnalyticsService.envoyerEvenement("sentier", "creation", lthis.nouveauSentierTitre);

					lthis.initialiserNouveauSentier(lthis.nouveauSentierTitre);
					lthis.nouveauSentierTitre = "";
				}
			}, 
			function(data) {
				window.alert(data);
			});
		} else {
			window.alert("Le nom du sentier n'est pas valide, vérifiez que n'avez pas saisi un nom vide ou qui existe déjà.");
		}
	};
	
	this.supprimerSentier = function(sentier) {
		var lthis = this;
		if(window.confirm("Êtes-vous sûr de vouloir supprimer ce sentier ?")) {
			smartFormService.supprimerSentier(sentier.titre,
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
					// stats
					googleAnalyticsService.envoyerEvenement("sentier", "suppression", sentier.titre);
				}
			}, 
			function(data) {
				console.log('C\'est pas bon !');
			});
		}
	};
	
	this.ajouterFicheASentier = function(sentier, fiche) {
		var lthis = this;
		if(!lthis.sentierSelectionneContientFiche(fiche.tag)) {
			smartFormService.ajouterFicheASentier(sentier.titre, fiche.tag,
			function(data) {
				if(data == 'OK') {
					lthis.sentierSelectionne.fiches.push(fiche);
					// stats
					googleAnalyticsService.envoyerEvenement("sentier", "ajout-fiche", '{"sentier": "' + sentier.titre + '", "fiche": {"tag": "' + fiche.tag + '", "nom_sci": "' + fiche.infos_taxon.nom_sci + '", "referentiel": "' + fiche.infos_taxon.referentiel + '"}}');
				}
			}, 
			function(data) {
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
					// stats
					googleAnalyticsService.envoyerEvenement("sentier", "suppression-fiche", '{"sentier": "' + sentier.titre + '", "fiche": {"tag": "' + fiche.tag + '", "nom_sci": "' + fiche.infos_taxon.nom_sci + '", "referentiel": "' + fiche.infos_taxon.referentiel + '"}}');
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
	
	if(this.afficherSentiers) {
		this.getSentiers();
	}
});