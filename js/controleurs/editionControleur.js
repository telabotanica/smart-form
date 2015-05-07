smartFormApp.controller('EditionControleur', function ($scope, $rootScope, $sce, wikiniService) {
	
	this.fiche_edition = {};
	this.fiche_edition.sections = [];
	
	var lthis = this;
	$scope.$on('edition.editer-fiche', function(event, fiche) {
		// changement d'état pour afficher le formulaire
		$rootScope.$broadcast('etat.changement-etat', "edition");
		lthis.editerFiche(fiche);
	});
	
	this.editerFiche = function(fiche) {
		var lthis = this;
		wikiniService.getFichePourEdition(fiche, 
		function(data) {
			lthis.fiche_edition.sections = data.sections;
			lthis.fiche_edition.tag = fiche.tag;
			lthis.fiche_edition.nom_sci = fiche.infos_taxon.nom_sci;
			lthis.fiche_edition.referentiel = fiche.infos_taxon.referentiel;
			lthis.fiche_edition.existe = true;
			// Dès que le formulaire d'édition est appelé, il crée la fiche
			$rootScope.$broadcast('edition.fiche-editee', lthis.fiche_edition);
		}, function(data) {
			// rien à faire en cas d'échec
		});
	};
	
	this.afficherFormEditionSection = function(fiche, titre) {
		
		this.fiche_edition.section_edition = {};
		var lthis = this;
		wikiniService.getFicheSectionPourEdition(fiche, titre,
		function(data) {
			lthis.fiche_edition.section_edition.titre = titre;
			lthis.fiche_edition.section_edition.html_brut_original = data.texte;
			lthis.fiche_edition.section_edition.html_brut = data.texte;
		}, function(data) {
			// rien à faire en cas d'échec
		});
	};
		
	this.annulerEditionSection = function(fiche_edition, titre) {
		this.fiche_edition.section_edition = {};
	};
	
	this.validerEditionSection = function(fiche_edition, titre) {
		var lthis = this;
		texte_saisi = this.fiche_edition.section_edition.html_brut;
		if(texte_saisi != this.fiche_edition.section_edition.html_brut_original) {		
			wikiniService.validerEditionSection(fiche_edition, titre, texte_saisi, 
				function() {
					wikiniService.getSectionFiche(fiche_edition, titre, 
					function(data) {
						section = data.texte;
						// C'est moche mais en attendant mieux on met à jour l'affichage manuellement
						// On ne peut pas faire de modèle bi directionnel avec du html échappé
						var elm = angular.element(document.getElementById(titre));
						elm.text(section);
						lthis.fiche_edition.section_edition = {};
					}, 
					function(data) {
						// rien à faire en cas d'échec
					});
				},
				function(data) {
					// rien à faire en cas d'échec
				});
		} else {
			lthis.annulerEditionSection();
		}
	};
	
	this.trustAsHtml = function(html) {
		return $sce.trustAsHtml(html);
	};
});