smartFormApp.controller('RechercheControleur', function ($scope, $rootScope, etatApplicationService, paginationService, smartFormService) {

	this.referentiels = config.referentiels;

	this.recherche = {};
	this.recherche.texte = "";
	this.recherche.fichesExistantes = false;
	this.recherche.referentiel = this.referentiels[0];
	this.recherche.nomVernaculaire = false;
	this.recherche.referentielVerna = this.recherche.referentiel.noms_vernaculaires;
	this.recherche.filtreSousEnsemble = this.recherche.referentiel.filtre;

	this.surChangementTypeRecherche = function(type) {
		this.recherche.nomVernaculaire = (type == "vernaculaire");
		var evt = (type == "vernaculaire") ? 'approximative' : 'exacte';
		paginationService.messagePaginationApproximative = "Attention, la pagination peut être imprécise dans le cas de la recherche par nom vernaculaire. "+
															"Le nombre d'éléments par pages peut ne pas être constant";
		$rootScope.$broadcast('pagination.pagination-'+evt);
	};

	this.surChangementRecherche = function() {
		if(!etatApplicationService.premierChargement) {
			etatApplicationService.rechercheModifiee = true;
		}
		this.recherche.referentielVerna = this.recherche.referentiel.noms_vernaculaires;
		this.recherche.filtreSousEnsemble = this.recherche.referentiel.filtre;

		if(this.recherche.referentielVerna == null) {
			this.recherche.nomVernaculaire = false;
		}

		etatApplicationService.premierChargement = false;
	};

	this.soumettreRecherche = function() {
		this.majEtatApplicationService();
		$rootScope.$broadcast('recherche.recherche-effectuee');
	};

	this.majEtatApplicationService = function() {
		etatApplicationService.recherche.texte = this.recherche.texte;
		etatApplicationService.recherche.fichesExistantes = this.recherche.fichesExistantes;
		etatApplicationService.recherche.referentiel = this.recherche.referentiel;
		etatApplicationService.recherche.nomVernaculaire = this.recherche.nomVernaculaire;
		etatApplicationService.recherche.referentielVerna = this.recherche.referentielVerna;
		etatApplicationService.recherche.filtreSousEnsemble = this.recherche.filtreSousEnsemble;
	};

	this.getNomsAsync = function() {
	    return smartFormService.getListeFichesSmartFloreAsync(this.recherche, 0, 10);
	};
});
