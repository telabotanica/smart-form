smartFormApp.controller('BoiteUtilisateurControleur', function ($scope, $rootScope, etatApplicationService) {
	
	this.utilisateur = {};
	this.utilisateur.connecte = false;
	this.utilisateur.courriel = "";
	this.utilisateur.mdp = "";
	
	$scope.$on('utilisateur.utilisateur-connecte', function(event, utilisateur) {

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
			lthis.utilisateur.id = data.id;
			lthis.utilisateur.prenom = data.prenom;
			lthis.utilisateur.nom = data.nom;
			lthis.utilisateur.connecte = true;
				
			etatApplicationService.utilisateur = {};
			etatApplicationService.utilisateur.connecte = true; 
			etatApplicationService.utilisateur.id = data.id; 
			etatApplicationService.utilisateur.nomWiki = data.nomWiki; 
		},
		function() {
			
		});
	};
	
	this.connaitreEtatUtilisateur();
});