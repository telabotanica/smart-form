smartFormApp.controller('BoiteUtilisateurControleur', function ($scope, $rootScope, etatApplicationService, liensService) {
	
	this.utilisateur = {};
	this.urlInscription = config.url_inscription;
	
	this.message = '';
	this.erreur = false;
	this.courrielOK = false;
	this.mdpOK = false;
	
	// au cas où autre chose nous aurait connecté dans l'application
	$scope.$on('utilisateur.utilisateur-connecte', function(event, utilisateur) {
		this.utilisateur = utilisateur;
	});

	var lthis = this;
	this.connecterUtilisateur = function() {
		if(this.formulaireValide()) {		
			etatApplicationService.connecterUtilisateur(this.utilisateur, 
			function(data) {
				lthis.connaitreEtatUtilisateur();
				lretirerEnErreur();
			},
			function(data) {
				lthis.mettreEnErreur();
			});
		} else {
			lthis.mettreEnErreur();
		}
	};
	
	this.verifierValiditeFormulaire = function() {
		this.courrielOK = this.courrielValide();
		this.mdpOK = this.mdpValide();
		
		if(this.formulaireValide()) {
			this.retirerEnErreur();
		}
	};
	
	this.formulaireValide = function() {
		return this.courrielValide() && this.mdpValide();
	}
	
	this.courrielValide = function() {
		return !!this.utilisateur.courriel && this.utilisateur.courriel != '';
	};
	
	this.mdpValide = function() {
		return !!this.utilisateur.mdp && this.utilisateur.mdp != '';
	};
	
	this.mettreEnErreur = function() {
		lthis.message = "Courriel / Mot de passe incorrect";
		this.erreur = true;
	};
	
	this.retirerEnErreur = function() {
		lthis.message = "";
		this.erreur = false;
	};
	
	this.connaitreEtatUtilisateur = function() {
		etatApplicationService.connaitreEtatUtilisateur( 
		function(data) {
			if(!!data && !!data.id) {
				lthis.utilisateur = data;
				lthis.utilisateur.connecte = true;
				etatApplicationService.utilisateur = lthis.utilisateur;				
				$rootScope.$broadcast('utilisateur.utilisateur-connecte', etatApplicationService.utilisateur);
			}
		},
		function() {
			
		});
	};
	
	this.deconnecterUtilisateur = function() {
		etatApplicationService.deconnecterUtilisateur( 
		function(data) {
			lthis.initialiserUtilisateurVide();
			etatApplicationService.utilisateur = lthis.utilisateur;
			$rootScope.$broadcast('utilisateur.utilisateur-deconnecte');
		},
		function() {
			
		});
	};
	
	this.initialiserUtilisateurVide = function() {
		this.utilisateur = {};
		this.utilisateur.connecte = false;
		this.utilisateur.id = "";
		this.utilisateur.prenom = "";
		this.utilisateur.nom = "";
		this.utilisateur.courriel = "";
		this.utilisateur.nomWiki = "";
		this.utilisateur.mdp = "";
	};
	
	this.initialiserUtilisateurVide();
	this.connaitreEtatUtilisateur();
});