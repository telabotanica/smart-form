smartFormApp.controller('SentiersControleur', function ($scope, $rootScope, smartFormService, etatApplicationService, liensService) {
	
	this.sentiers = [];
	this.sentierSelectionne = {};
	this.sentierSelectionne.titre = "";
	this.sentierSelectionne.fiches = [];
	
	this.nouveauSentier = "";
	
	this.afficherSentiers = etatApplicationService.utilisateur.connecte;
	
	this.liensService = liensService;
	
	var lthis = this;
	$scope.$on('utilisateur.utilisateur-connecte', function(event, utilisateur) {
		lthis.afficherSentiers = true;
	});
	
	$scope.$on('utilisateur.utilisateur-deconnecte', function(event) {
		lthis.afficherSentiers = false;
	});
	
	this.getSentiers = function() {
		var lthis = this;
		smartFormService.getSentiers(etatApplicationService.utilisateur.nomWiki,
		function(data) {
			lthis.sentiers = data.resultats;
			if(lthis.sentiers.length > 0) {
				lthis.sentierSelectionne = lthis.sentiers[0];
			}
			console.log(lthis.sentiers);
		}, 
		function() {
			
		});
	};
	
	this.ajouterSentier = function() {
		var lthis = this;
		if(this.verifierValiditeSentier()) {
			smartFormService.ajouterSentier(etatApplicationService.utilisateur.nomWiki, this.nouveauSentier,
			function(data) {
				if(data == 'OK') {
					lthis.initialiserNouveauSentier(lthis.nouveauSentier);
					lthis.nouveauSentier = "";
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
		this.sentierSelectionne = nouveauSentier;
	}
	
	this.verifierValiditeSentier = function() {
		return !!this.nouveauSentier && !this.contientSentier(this.nouveauSentier);
	};
	
	this.contientSentier = function(sentier) {
	    var i;
	    for (i = 0; i < this.sentiers.length; i++) {
	        if (this.sentiers[i] === sentier) {
	        	return true;
	        }
	    }
	    return false;
	};
	
	this.surChangementSaisieSentier = function() {
		
	};	
	
	this.getSentiers();
});