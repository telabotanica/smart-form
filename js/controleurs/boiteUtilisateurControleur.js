smartFormApp.controller('BoiteUtilisateurControleur', function ($scope, $rootScope, etatApplicationService) {
	
	this.utilisateur = {};
	
	// au cas où autre chose nous aurait connecté dans l'application
	$scope.$on('utilisateur.utilisateur-connecte', function(event, utilisateur) {
		this.utilisateur = utilisateur;
	});

	var lthis = this;
	this.connecterUtilisateur = function() {
		etatApplicationService.connecterUtilisateur(this.utilisateur, 
		function(data) {
			lthis.connaitreEtatUtilisateur();
		},
		function() {
			
		});
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