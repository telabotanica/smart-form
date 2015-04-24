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
	$scope.recherche.referentiel = $scope.referentiels[0];
	$scope.rechercheModifiee = false;
	$scope.premierChargement = true;
	
	$scope.pagination = {};
	$scope.pagination.pages = [];
	$scope.pagination.nbPages = 0;
	$scope.pagination.totalResultats = 0;
	$scope.pagination.taillePage = 20;
	$scope.pagination.pageCourante = 0;
	
	$scope.$watch('recherche', function(newVal, oldVal) {
		if(!$scope.premierChargement) {
			$scope.rechercheModifiee = true;
		}
		$scope.premierChargement = false;
	}, true);
	
	$scope.getNbPages = function() {
		return $scope.pagination.nbPages; 
	};
	
	$scope.resetPagination = function() {
		$scope.pagination.pages = [];
		$scope.pagination.nbPages = 0;
		$scope.pagination.totalResultats = 0;
		$scope.pagination.taillePage = 20;
		$scope.pagination.pageCourante = 0;
	};

	$scope.getFiches = function() {

		$scope.chargement = true;

		if($scope.rechercheModifiee && !$scope.premierChargement) {
			$scope.resetPagination();
			$scope.rechercheModifiee = false;
		}
		
		referentiel = "referentiel="+(!$scope.recherche.referentiel ? '%' : $scope.recherche.referentiel);
		recherche = "&recherche="+(!$scope.recherche.texte ? '%' : $scope.recherche.texte);
		pages_existantes = '&pages_existantes='+(!!$scope.recherche.fichesExistantes);
		pagination = '&debut='+($scope.pagination.pageCourante*$scope.pagination.taillePage)+"&limite="+($scope.pagination.taillePage);

		$http.get(config.url_service+'?'+referentiel+recherche+pages_existantes+pagination).
		success(function(data, status, headers, config) {

			$scope.construireNbPages(data.pagination);
			$scope.fiches = data.resultats;
			$scope.chargement = false;
		}).
		error(function(data, status, headers, config) {
			$scope.chargement = false;
		});
	};
	
	$scope.construireNbPages = function(paginationResultats) {
		$scope.pagination.totalResultats = paginationResultats.total;
		$scope.pagination.nbPages = Math.ceil($scope.pagination.totalResultats/$scope.pagination.taillePage);
		$scope.pagination.pages = [];
		
		var intervalleAvantApres = 6;
		// Cas où l'on affiche toutes les pages sans se prendre la tête
		if($scope.pagination.nbPages <= 2*intervalleAvantApres) {
			for(var i = 0; i < $scope.pagination.nbPages; i++) {
				$scope.pagination.pages.push(i+1);	
			}
		} else {						
			var debutIntervalleGauche = Math.max(1, $scope.pagination.pageCourante - intervalleAvantApres);
			var finIntervalleGauche = $scope.pagination.pageCourante;
			
			var debutIntervalleDroite = finIntervalleGauche + 1;
			var finIntervalleDroite = Math.min($scope.pagination.pageCourante + intervalleAvantApres, $scope.pagination.nbPages - 2);
			
			// Si on est au début de la liste et qu'on a moins de pages à gauche qu'à droite on en rajoute 
			// à droite 
			var decalageADroite = $scope.pagination.pageCourante - (debutIntervalleGauche);
			if(decalageADroite < intervalleAvantApres) {
				finIntervalleDroite = finIntervalleDroite + (intervalleAvantApres - decalageADroite) - 1;
				finIntervalleDroite = Math.min(finIntervalleDroite, $scope.pagination.nbPages - 2);
			}
			
			// Si on est à la fin de la liste et qu'on a moins de pages à droite qu'à gauche on en rajoute 
			// à gauche 
			var decalageAGauche = finIntervalleDroite - $scope.pagination.pageCourante;
			if(decalageAGauche < intervalleAvantApres) {
				debutIntervalleGauche = debutIntervalleGauche - (intervalleAvantApres - decalageAGauche) + 1;
				debutIntervalleGauche = Math.max(debutIntervalleGauche, 0);
			}
			
			// page de début obligatoire
			$scope.pagination.pages.push(1);
			
			if($scope.pagination.pageCourante - intervalleAvantApres > 0) {
				$scope.pagination.pages.push("...");
			}
			
			for(var i = debutIntervalleGauche; i <= finIntervalleGauche; i++) {
				$scope.pagination.pages.push(i+1);
			}
						
			for(var i = debutIntervalleDroite; i <= finIntervalleDroite; i++) {
				$scope.pagination.pages.push(i+1);
			}
			
			if($scope.pagination.pageCourante + intervalleAvantApres < $scope.pagination.nbPages - 1) {
				$scope.pagination.pages.push("...");
			}
			
			if($scope.pagination.pageCourante < $scope.pagination.nbPages - 1) {
				// page de fin obligatoire si non incluse par la boucle précédente
				$scope.pagination.pages.push($scope.pagination.nbPages);
			}
		}

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
	
	$scope.pagePrecedente = function() {
		if($scope.pagination.pageCourante != 0) {
			$scope.changerPage($scope.pagination.pageCourante);
		}
	};
	
	$scope.pageSuivante = function() {
		if($scope.pagination.pageCourante != $scope.pagination.nbPages - 1) {
			$scope.changerPage($scope.pagination.pageCourante + 2);
		}
	};
	
	$scope.changerPage = function(page) {

		// Pas besoin de changer de page si on est déjà sur la page demandée
		// où si l'on a cliqué sur une case de remplissage
		if($scope.pagination.pageCourante == page - 1 || page == '...') {
			return;
		}
		
		$scope.pagination.pageCourante = page - 1;
		if(page - 1 < 0) {
			$scope.pagination.pageCourante = 0;
		}
		
		if(page -1 > $scope.pagination.nbPages) {
			$scope.pagination.pageCourante = $scope.pagination.nbPages;
		}
		$scope.getFiches();
	};
	
	$scope.getBorneMinIntervalleAffiche = function() {
		return $scope.pagination.pageCourante * $scope.pagination.taillePage + 1;
	};
	
	$scope.getBorneMaxIntervalleAffiche = function() {
		return Math.min($scope.pagination.totalResultats, ($scope.pagination.pageCourante+1) * $scope.pagination.taillePage);
	};
	
	$scope.getFiches();
});