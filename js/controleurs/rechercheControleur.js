smartFormApp.controller('RechercheControleur', function ($scope, $rootScope, etatApplicationService) {
	
	this.referentiels = config.referentiels;
	
	this.recherche = {};
	this.recherche.texte = "";
	this.recherche.referentiel = config.referentiels[0];
	this.recherche.fichesExistantes = false;
	
	this.surChangementRecherche = function() {
		if(!etatApplicationService.premierChargement) {
			etatApplicationService.rechercheModifiee = true;
		}
		etatApplicationService.premierChargement = false;
	}
	
	this.soumettreRecherche = function() {
		this.majEtatApplicationService();
		$rootScope.$broadcast('recherche.recherche-effectuee');
	};
	
	this.majEtatApplicationService = function() {
		etatApplicationService.recherche.texte = this.recherche.texte;
		etatApplicationService.recherche.fichesExistantes = this.recherche.fichesExistantes;
		etatApplicationService.recherche.referentiel = this.recherche.referentiel;
	};
});