smartFormApp.controller('EditionControleur', function ($scope, $rootScope, $sce, wikiniService, smartFormService, googleAnalyticsService) {

	this.fiche_edition = {};
	this.fiche_edition.sections = [];

	this.sectionsOrdonnees = config.sections_pages;

	this.cacherLienRetour = false;

	var lthis = this;
	$scope.$on('edition.editer-fiche', function(event, fiche) {
		// changement d'état pour afficher le formulaire
		$rootScope.$broadcast('etat.changement-etat', "edition");
		lthis.editerFiche(fiche);
	});

	$scope.$on('edition.charger-editer-fiche', function(event, infos) {
		// TODO: vérifier que l'on est connecté et si ça n'est pas le cas afficher
		// un formulaire d'identification en pleine page ?

		// on cache les liens de retours (ceci sert pour une édition directe à partir d'un lien)
		// donc retourner à l'application n'a pas de sens ici
		lthis.cacherLienRetour = true;
		// changement d'état pour afficher le formulaire
		$rootScope.$broadcast('etat.changement-etat', "edition");
		lthis.chargerEtEditerFiche(infos.referentiel, infos.num_tax);
	});

	this.chargerEtEditerFiche = function(referentiel, num_tax) {
		smartFormService.getFicheSmartFlore(referentiel, num_tax,
		function(data) {
			var fiche = data.resultats.pop();
			lthis.editerFiche(fiche);
		}, function(data) {

		});
	};

	this.editerFiche = function(fiche) {
		var lthis = this;
		lthis.fiche_edition = {};
		wikiniService.getFichePourEdition(fiche,
		function(data) {
			lthis.fiche_edition.sections = data.sections;
			lthis.fiche_edition.tag = fiche.tag;
			lthis.fiche_edition.nom_sci = fiche.infos_taxon.nom_sci;
			lthis.fiche_edition.referentiel = fiche.infos_taxon.referentiel;
			lthis.fiche_edition.existe = true;
			// Dès que le formulaire d'édition est appelé, il crée la fiche si elle n'existe pas
			// C'est ultra naze, faudrait seulement le faire si y'a quelque chose d'enregistré après, histoire d'éviter les fiches vides
			$rootScope.$broadcast('edition.fiche-editee', lthis.fiche_edition);
			// stats
			googleAnalyticsService.envoyerEvenement("fiche", "edition", '{"tag": "' + fiche.tag + '", "nom_sci": "' + fiche.infos_taxon.nom_sci + '", "referentiel": "' + fiche.infos_taxon.referentiel + '"}');
		}, function(data) {
			// rien à faire en cas d'échec
		});
	};

	this.afficherFormEditionSection = function(fiche, titre) {

		this.fiche_edition.section_edition = {};
		var lthis = this;

		// Pour conserver la taille de l'élément
		// TODO: c'est moche, ça ne devrait pas avoir sa place
		// dans le controleur (on pourrait faire une directive)
		var elm = angular.element(document.getElementById(titre));
		var hauteurForm = elm[0].offsetHeight;

		var form = angular.element(document.getElementById("formulaire_edition_"+titre));
		form.css('height', hauteurForm+"px");

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
						elm.html(section);
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
