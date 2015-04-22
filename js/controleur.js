var smartFormApp = angular.module('smartFormApp', ['ngSanitize']);
smartFormApp.controller('SmartFormControleur', function ($scope, $http, $sce, $document) {
	
	// globale provenant de config.js
	$scope.config = config;
	
	$scope.etat = "liste";
	$scope.chargement = true;
	
	$scope.fiches = [];
	$scope.referentiels = config.referentiels;
	$scope.recherche = {};
	$scope.recherche.texte = "";
	$scope.recherche.referentiel = "";

	$scope.getFiches = function() {

		$scope.chargement = true;
		
		referentiel = !$scope.recherche.referentiel ? '%' : $scope.recherche.referentiel;
		texte = !$scope.recherche.texte ? '%' : $scope.recherche.texte;
		
		nom_fiche = config.nom_fiche.replace("{referentiel}", referentiel);
		nom_fiche = nom_fiche.replace("{num_tax}", texte);

		$http.get(config.url_service+'?tpl_nom_page='+nom_fiche).
		success(function(data, status, headers, config) {
			$scope.fiches = data;
			$scope.chargement = false;
		}).
		error(function(data, status, headers, config) {
			$scope.chargement = false;
		});
	};
	
	$scope.getFiche = function(fiche) {
		$scope.chargement = true;
		
		url = $scope.formaterUrlSectionWiki(fiche.tag, config.sections_pages.join(), 'text/html'); 
		
		$http.get(url).
		success(function(data, status, headers, config) {
			$scope.fiche_edition = data;
			$scope.fiche_edition.tag = fiche.tag;
			$scope.fiche_edition.nom_sci = fiche.infos_taxon.nom_sci;
			$scope.fiche_edition.referentiel = fiche.infos_taxon.referentiel;
			$scope.chargement = false;
		}).
		error(function(data, status, headers, config) {
			$scope.chargement = false;
		});
	};
	
	$scope.getUrlPageWiki = function(fiche) {
		return $scope.config.url_wikini.replace("{tag}" , fiche.tag);
	};
	
	$scope.afficherQrCode = function(fiche) {
		url = config.url_qr_code.replace('{referentiel}', fiche.infos_taxon.referentiel.toLowerCase());
		url = url.replace('{num_nom}', fiche.infos_taxon.num_nom);
		window.open(url);
	};
	
	$scope.changerEtat = function(etat) {
		$scope.etat = etat;
	};
	
	$scope.editerFiche = function(fiche) {
		// charger les données de fiche
		$scope.etat = "edition";
		$scope.getFiche(fiche);
	};
	
	$scope.afficherFiche = function(fiche) {
		console.log(fiche);
		referentiel_fiche = ""+fiche.infos_taxon.referentiel;
		url_fiche = $scope.config.url_fiche_mobile.replace('{referentiel}', referentiel_fiche.toLowerCase());
		url_fiche = url_fiche.replace('{num_nom}', fiche.infos_taxon.num_nom);
		window.open(url_fiche);
	};
	
	$scope.afficherFormEdition = function(fiche, titre, section) {
		
		url = $scope.formaterUrlSectionWiki(fiche.tag, titre, 'text/plain'); 
		
		$scope.fiche_edition.section_edition = {};
				
		$http.get(url).
		success(function(data, status, headers, config) {
			$scope.fiche_edition.section_edition.titre = titre;
			$scope.fiche_edition.section_edition.html_brut_original = data.texte;
			$scope.fiche_edition.section_edition.html_brut = data.texte;
		}).
		error(function(data, status, headers, config) {
			// Afficher l'erreur
		});
	};
	
	$scope.getSectionFiche = function(fiche, titre, section) {
		
		url = $scope.formaterUrlSectionWiki(fiche.tag, titre, 'text/html'); 
		
		$http.get(url).
		success(function(data, status, headers, config) {
			section = data.texte;
			// C'est moche mais en attendant mieux on met à jour l'affichage manuellement
			// On ne peut pas faire de modèle bi directionnel avec du html échappé
			var elm = angular.element(document.getElementById(titre));
			elm.text(section);
			$scope.fiche_edition.section_edition = {};	
		}).
		error(function(data, status, headers, config) {
			// Afficher l'erreur
		});
	};
	
	
	$scope.annulerEditionSection = function(fiche_edition, titre, section) {
		$scope.fiche_edition.section_edition = {};
	};
	
	$scope.validerEditionSection = function(fiche_edition, titre, section) {
		texte_saisi = $scope.fiche_edition.section_edition.html_brut;
		if(texte_saisi != $scope.fiche_edition.section_edition.html_brut_original) {
						
			url = $scope.formaterUrlSectionWiki(fiche_edition.tag, titre, 'text/plain');
			donnees_post = {pageContenu : texte_saisi, pageSectionTitre : titre};
			
			// Besoin d'un objet particulier ici car sinon angular poste du json
			$http({
			    method: 'POST',
			    url: url,
			    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			    transformRequest: function(obj) {
			        var str = [];
			        for(var p in obj)
			        str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
			        return str.join("&");
			    },
			    data: donnees_post
			}).
			success(function(data, status, headers, config) {
				$scope.getSectionFiche(fiche_edition, titre, section);
			}).
			error(function(data, status, headers, config) {
				// afficher erreur
			});
		} else {
			$scope.annulerEditionSection();
		}
	};
	
	$scope.trustAsHtml = function(html) {
		return $sce.trustAsHtml(html);
	};
	
	$scope.formaterUrlSectionWiki = function(tag, titre, format) {
		url = config.url_section_wiki.replace('{pageTag}', tag);
		url = url.replace('{sectionTitre}', window.encodeURIComponent(titre));
		url = url.replace('{format}', 'text/plain');
		
		return url;
	};
	
	$scope.getFiches();
});