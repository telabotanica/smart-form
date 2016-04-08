smartFormApp.controller('SentiersControleur', function ($scope, $rootScope, $window, $http, smartFormService, etatApplicationService, liensService, googleAnalyticsService, geolocation) {

	this.sentiers = [];
	this.sentierSelectionne = creerObjetSentierVide();

	this.nouveauSentierTitre = "";

	this.afficherSentiers = etatApplicationService.utilisateur.connecte;
	this.utilisateurNomWiki = etatApplicationService.utilisateur.nomWiki;

	this.liensService = liensService;
	this.chargementSentier = false;

	var lthis = this;

	$scope.$on('utilisateur.utilisateur-connecte', function(event, utilisateur) {
		lthis.utilisateurNomWiki = utilisateur.nomWiki;
		lthis.afficherSentiers = utilisateur.connecte;
		lthis.getSentiers();
	});

	$scope.$on('utilisateur.utilisateur-deconnecte', function(event) {
		lthis.afficherSentiers = false;
		lthis.utilisateurNomWiki = "";
	});

	$scope.$on('dropEvent', function(evt, dragged, dropped) {
		lthis.ajouterFicheASentier(lthis.sentierSelectionne, dragged);
	});

	$scope.$on('edition.fiche-editee', function(event, fiche) {
	    var i;
	    for (i = 0; i < lthis.sentierSelectionne.fiches.length; i++) {
	        if (lthis.sentierSelectionne.fiches[i].tag === fiche.tag) {
	        	lthis.sentierSelectionne.fiches[i].existe = true;
	        	lthis.sentierSelectionne.fiches[i].nb_revisions = parseInt(lthis.sentierSelectionne.fiches[i].nb_revisions) + 1;
	        	return;
	        }
	    }
	});

	function creerObjetSentierVide() {
		return {
			titre: '',
			fiches: [],
			localisation: {
				nbIndividus: 0
			},
			auteur: ''
		}
	}

	this.editerFiche = function(fiche) {
		$rootScope.$broadcast('edition.editer-fiche', fiche);
	};

	this.surChangementSentier = function() {
		this.chargementSentier = true;
		smartFormService.getFichesASentier(this.sentierSelectionne.titre,
			function(data) {
				lthis.sentierSelectionne.fiches = data.resultats;
			},
			function(data) {
				console.log('C\'est pas bon !');
			}
		);

		smartFormService.getLocalisationASentier(this.sentierSelectionne.titre,
			function(data) {
				lthis.sentierSelectionne.localisation = data.localisation;
				lthis.chargementSentier = false;
			},
			function(data) {
				console.log('C\'est pas bon !');
			}
		);
	};

	this.getSentiers = function() {
		var lthis = this;
		smartFormService.getSentiers(etatApplicationService.voirTousLesSentiers,
			function(data) {
				lthis.sentiers = data.resultats ? data.resultats : [] ;
				if (lthis.sentiers.length > 0) {
					lthis.sentierSelectionne = lthis.sentiers[0];
					lthis.surChangementSentier();
				}
				lthis.afficherSentiers = etatApplicationService.utilisateur.connecte;
			},
			function(data) {
				console.log('C\'est pas bon !');
			}
		);
	};

	this.ajouterSentier = function() {
		var lthis = this;
		if(this.verifierValiditeSentier()) {
			smartFormService.ajouterSentier(this.nouveauSentierTitre,
			function(data) {
				if(data == 'OK') {
					// stats
					googleAnalyticsService.envoyerEvenement("sentier", "creation", lthis.nouveauSentierTitre);

					lthis.initialiserNouveauSentier(lthis.nouveauSentierTitre);
					lthis.nouveauSentierTitre = "";
				}
			},
			function(data) {
				window.alert(data);
			});
		} else {
			window.alert("Le nom du sentier n'est pas valide, vérifiez que n'avez pas saisi un nom vide ou qui existe déjà.");
		}
	};

	this.supprimerSentier = function(sentier) {
		var lthis = this;
		if(window.confirm("Êtes-vous sûr de vouloir supprimer ce sentier ?")) {
			smartFormService.supprimerSentier(sentier.titre,
				function(data) {
					if(data == 'OK') {
						lthis.supprimerSentierDeLaListe(sentier);
						if(lthis.sentiers.length > 0) {
							lthis.sentierSelectionne = lthis.sentiers[lthis.sentiers.length - 1];
						} else {
							lthis.sentierSelectionne = creerObjetSentierVide();
						}

						lthis.surChangementSentier();

						// suppression de la localisation également
						smartFormService.supprimerSentierLocalisation(sentier.titre,
							function() {
								// console.log('C\'est cool !');
							},
							function() {
								console.log('C\'est pas bon !');
							}
						);
						// stats
						googleAnalyticsService.envoyerEvenement("sentier", "suppression", sentier.titre);
					}
				},
				function(data) {
					console.log('C\'est pas bon !');
				}
			);
		}
	};

	this.ajouterFicheASentier = function(sentier, fiche) {
		var lthis = this;
		if(!lthis.sentierSelectionneContientFiche(fiche.tag)) {
			smartFormService.ajouterFicheASentier(sentier.titre, fiche.tag,
			function(data) {
				if(data == 'OK') {
					lthis.sentierSelectionne.fiches.push(fiche);
					// stats
					googleAnalyticsService.envoyerEvenement("sentier", "ajout-fiche", '{"sentier": "' + sentier.titre + '", "fiche": {"tag": "' + fiche.tag + '", "nom_sci": "' + fiche.infos_taxon.nom_sci + '", "referentiel": "' + fiche.infos_taxon.referentiel + '"}}');
				}
			},
			function(data) {
				console.log('C\'est pas bon !');
			});
		}
	};

	this.supprimerFicheASentier = function(sentier, fiche) {
		var lthis = this;
		if(window.confirm("Êtes-vous sûr de vouloir supprimer cette fiche du sentier ?")) {
			smartFormService.supprimerFicheASentier(sentier.titre, fiche.tag,
			function(data) {
				if(data == 'OK') {
					lthis.supprimerFicheDuSentier(sentier, fiche);
					// stats
					googleAnalyticsService.envoyerEvenement("sentier", "suppression-fiche", '{"sentier": "' + sentier.titre + '", "fiche": {"tag": "' + fiche.tag + '", "nom_sci": "' + fiche.infos_taxon.nom_sci + '", "referentiel": "' + fiche.infos_taxon.referentiel + '"}}');
				}
			},
			function() {
				console.log('C\'est pas bon !');
			});
		}
	};

	this.initialiserNouveauSentier = function(titre) {
		var nouveauSentier = creerObjetSentierVide();
		nouveauSentier.titre = titre;
		nouveauSentier.auteur = lthis.utilisateurNomWiki;
		this.sentiers.push(nouveauSentier);
		this.sentierSelectionne = this.sentiers[this.sentiers.length - 1];
	};

	this.verifierValiditeSentier = function() {
		return !!this.nouveauSentierTitre && !this.contientSentier(this.nouveauSentierTitre);
	};

	this.contientSentier = function(sentierTitre) {
	    var i;
	    for (i = 0; i < this.sentiers.length; i++) {
	        if (this.sentiers[i].titre === sentierTitre) {
	        	return true;
	        }
	    }
	    return false;
	};

	this.sentierSelectionneContientFiche = function(ficheTitre) {
	    var i;
	    for (i = 0; i < this.sentierSelectionne.fiches.length; i++) {
	        if (this.sentierSelectionne.fiches[i].tag === ficheTitre) {
	        	return true;
	        }
	    }
	    return false;
	};

	this.supprimerSentierDeLaListe = function(sentier) {
	    var i;
	    for (i = 0; i < this.sentiers.length; i++) {
	    	 if (this.sentiers[i].titre === sentier.titre) {
	        	this.sentiers.splice(i, 1);
	        }
	    }
	    return false;
	};

	this.supprimerFicheDuSentier = function(sentier, fiche) {
	    var i;
	    for (i = 0; i < sentier.fiches.length; i++) {
	    	 if (sentier.fiches[i].tag === fiche.tag) {
	    		 sentier.fiches.splice(i, 1);
	        }
	    }
	    return false;
	};

	this.surChangementSaisieSentier = function() {
		//TODO: Avertir l'utilisateur en cas de saisie d'un sentier déjà existant ?
	};

	// ---------------- LOCALISATION ----------------

	$scope.$on('leafletDirectiveMap.click', function(event, args) {
		if (lthis.etape == 'localiser-sentier') {
			if (angular.isDefined(lthis.leafletConfig.markers)) {
				angular.merge(lthis.leafletConfig.markers, {
					sentier: creerMarkerSentier(args.leafletEvent.latlng)
				});
			} else {
				lthis.leafletConfig.markers = {
					sentier: creerMarkerSentier(args.leafletEvent.latlng)
				};
			}
		}
	});

	/**
	 * Créé un marker correspondant à l'entrée du sentier
	 *
	 * Prend en entrée un tableau de la forme : {
	 * 	lat: 28.42312581122
	 * 	lng:  3.48896549491
	 * }
	 * Sinon deux paramètres, lat et lng
	 *
	 * @method     creerMarkerSentier
	 * @param      {mixed}  latlng  Soit un tableau contenant lat et lng, soit juste lat
	 * @param      {mixed}  lng     Dans le premier cas est nul, sinon contient lng
	 * @return     {Object}
	 */
	function creerMarkerSentier(latlng, lng) {
		return {
			lat: angular.isDefined(lng) ? latlng : latlng.lat,
			lng: angular.isDefined(lng) ? lng : latlng.lng,
			message: "Cliquez pour me déplacer !",
			draggable: true,
			focus: false,
			opacity: 1,
			icon: {
                type: 'extraMarker',
                icon: 'ion-android-home',
                markerColor: 'red'
            }
		};
	}

	function creerMarkerIndividu(latlng, nom_sci, ficheTag) {
		return {
			lat: latlng.lat,
			lng: latlng.lng,
			message: nom_sci,
			draggable: true,
			focus: true,
			icon: {
                type: 'extraMarker',
                icon: 'ion-leaf',
                markerColor: 'green-light',
                shape: 'circle'
            },
            ficheTag: ficheTag
		};
	}

	$scope.$on('leafletDirectiveMarker.move', function(event, args) {
		if (lthis.etape == 'localiser-sentier') {
			angular.merge(lthis.leafletConfig.markers.sentier, args.leafletEvent.latlng);
		} else if (lthis.etape == 'localiser-individus') {
			angular.merge(lthis.leafletConfig.markers[args.modelName], args.leafletEvent.latlng);
		}
	});

	function initialiserLocalisation() {
		lthis.etape = 'localiser-sentier';
		lthis.titreModal = 'Cliquer pour placer le point d\'entrée du sentier';
		lthis.methodeLocalisation = 'auto';
		lthis.choixAdresses = [];

		lthis.tilesDict = {
			osm: {
				url: 'http://osm.tela-botanica.org/tuiles/osmfr/{z}/{x}/{y}.png',
				options: {
	                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
					maxZoom: 20
	            },
	            name: 'osm'
			},
			gmaps: {
				url: 'http://mt1.google.com/vt/lyrs=y@218131653&hl=fr&src=app&x={x}&y={y}&z={z}',
				options: {
					attribution: 'Map data &copy;'+new Date().getFullYear()+' <a href="http://maps.google.com">Google</a>',
					maxZoom: 21
				},
				name: 'gmaps'
			}
		};

		// Initialisation du centre du leaflet
		lthis.leafletCenter = {
			lat: 48.856614,
			lng: 2.3522219,
			zoom: 14
		};

		// Initialisation des markers
		lthis.markers = {};
		lthis.compteurDeMarkers = {};

		// Chargement des données dans le cas de l'édition d'un sentier
		if (angular.isDefined(lthis.sentierSelectionne.localisation.individus)) {
			// Les individus
			angular.forEach(lthis.sentierSelectionne.localisation.individus, function(value, key) {
				var fiche = getSentieSelectionneFicheParTag(value.ficheTag),
					markerName = creerNomMarker(value.ficheTag)
				;

				lthis.markers[markerName] = creerMarkerIndividu(value, fiche.nom_sci, value.ficheTag);
			});

			// Le centre de la carte sur le sentier
			angular.merge(lthis.leafletCenter, lthis.sentierSelectionne.localisation.sentier);
			lthis.leafletCenter.zoom = 18;
		}

		lthis.leafletConfig = {
			tiles: lthis.tilesDict.osm,
			center: lthis.leafletCenter
		};

		// En édition on ajoute à la config seulement le sentier pour la première étape
		if (angular.isDefined(lthis.sentierSelectionne.localisation.sentier)) {
			lthis.leafletConfig.markers = {
				sentier: creerMarkerSentier(lthis.sentierSelectionne.localisation.sentier)
			}
		}

		function getSentieSelectionneFicheParTag(tag) {
			var fiche;
			angular.forEach(lthis.sentierSelectionne.fiches, function(value, key){
				if (tag === value.tag) {
					fiche = value;
				}
			});

			return fiche;
		}
	};

	function creerNomMarker(tag) {
		if (angular.isDefined(lthis.compteurDeMarkers[tag])) {
			markerName = '' + tag + '#' + lthis.compteurDeMarkers[tag]
			lthis.compteurDeMarkers[tag] += 1;
		} else {
			markerName = '' + tag + '#0';
			lthis.compteurDeMarkers[tag] = 1;
		}

		return markerName;
	}

	this.ajouterMarker = function(fiche) {
		var marker = creerMarkerIndividu(lthis.leafletCenter, fiche.infos_taxon.nom_sci, fiche.tag),
			markerName = creerNomMarker(fiche.tag)
		;

		lthis.leafletConfig.markers[markerName] = marker;
	};

	this.toggleSuppressionMarkers = function() {
		lthis.modeSuppressionMarkers = !lthis.modeSuppressionMarkers;
	};

	$scope.$on('leafletDirectiveMarker.click', function(event, args) {
		if (lthis.modeSuppressionMarkers) {
			lthis.supprimerMarker(args.modelName);
		}
	});

	this.supprimerMarker = function(markerName) {
		if ($window.confirm('Confirmer la suppression de ' + lthis.leafletConfig.markers[markerName].message + ' ?')) {
			delete lthis.leafletConfig.markers[markerName];
		}
	};

	this.changerTiles = function(tiles) {
		lthis.leafletConfig.tiles = lthis.tilesDict[tiles];

		if (tiles == 'osm' && lthis.leafletCenter.zoom > 20) {
			lthis.leafletCenter.zoom = 20;
		}
	};

	this.demarrerLocalisationSentier = function() {
		$("#modalet").modal();

		$('#modalet').on('shown.bs.modal', function() {
		    setTimeout(function() {
		        lthis.modalOpened = true;
		        $scope.$broadcast('invalidateSize');
			}, 120);
		});

		initialiserLocalisation();
	};

	this.recupererLocaliteAuto = function() {
		lthis.choixAdresses = {};
		geolocation.getLocation().then(function(data) {
			var userLocation = {
				lat: data.coords.latitude,
				lng: data.coords.longitude
			};

			angular.merge(lthis.leafletCenter, userLocation);
		});
	};

	this.recupererLocaliteAdresse = function(adresse) {
		lthis.choixAdresses = {};
		$http({
			url: 'https://maps.google.com/maps/api/geocode/json?address=' + adresse + '&sensor=false',
			// url: 'https://nominatim.openstreetmap.org/search?q=' + adresse + '&format=json',
			method: 'GET',
			withCredentials: false
		}).success(function(data) {
			if (data.results.length == 1) {
				angular.merge(lthis.leafletCenter, data.results[0].geometry.location);
			} else if (data.results.length > 1) {
				lthis.choixAdresses = data.results;
			}
		}).error(function() {
			$window.alert('Oops, un problème est survenu, réessayer dans un instant')
		});
	};

	this.selectAdresse = function(adresse) {
		angular.merge(lthis.leafletCenter, adresse.geometry.location);
	};

	this.annuler = function() {
		if ($window.confirm('Les modifications en cours seront perdues')) {
			initialiserLocalisation();
			$("#modalet").modal('hide');
		}
	};

	this.validerLocalisationSentier = function() {
		// Restaure les markers d'individus dans le cas d'une édition du sentier
		angular.merge(lthis.leafletConfig.markers, lthis.markers);

		angular.merge(lthis.leafletConfig.markers.sentier, {
			draggable: false,
			opacity: 0.7,
			message: 'Entrée du sentier'
		});

		lthis.etape = 'localiser-individus';
		this.changerTiles('gmaps');
		lthis.titreModal = 'Cliquer sur une espèce pour créer un marqueur';

		// On décale légèrement le centre pour éviter le chevauchement des markers et on zoom
		angular.merge(lthis.leafletCenter, {
			lat: lthis.leafletConfig.markers.sentier.lat - 0.00005,
			lng: lthis.leafletConfig.markers.sentier.lng + 0.00005,
			zoom: 19
		});
	};

	this.validerLocalisationIndividus = function() {
		var markers = angular.copy(lthis.leafletConfig.markers);

		var localisation = {
			sentier:  {
				lat: markers.sentier.lat,
				lng: markers.sentier.lng
			},
			individus: {}
		};

		delete markers.sentier;

		angular.forEach(markers, function(marker, markerName) {
			localisation.individus[markerName] = {
				ficheTag: marker.ficheTag,
				lat: marker.lat,
				lng: marker.lng
			};
		});

		smartFormService.ajouterSentierLocalisation(lthis.sentierSelectionne.titre, localisation,
			function(data) {
				if (data == 'OK') {
					lthis.sentierSelectionne.localisation = localisation;
					lthis.sentierSelectionne.localisation.nbIndividus = Object.keys(localisation.individus).length;
					initialiserLocalisation();
					$('#modalet').modal('hide');
					// stats
					googleAnalyticsService.envoyerEvenement('sentier', 'ajout-localisation', '{ "sentier": "' + sentier.titre + '" }');
				}
			},
			function() {
				console.log('C\'est pas bon !');
			}
		);
	};

	this.retourLocalisationSentier = function() {
		if ($window.confirm('Abandonner les modifications en cours et revenir à la localisation du sentier ?')) {
			lthis.etape = 'localiser-sentier';
			lthis.changerTiles('osm');
			lthis.titreModal = 'Cliquer pour placer le point d\'entrée du sentier';

			angular.merge(lthis.leafletConfig.markers.sentier, {
				draggable: true,
				opacity: 1
			});

			// réinitialise les markers des individus tout en préservant l'entrée du sentier
			var sentier = angular.copy(lthis.leafletConfig.markers.sentier);
			angular.forEach(lthis.leafletConfig.markers, function(value, key) {
				delete lthis.leafletConfig.markers[key];
			});
			lthis.leafletConfig.markers.sentier = sentier;
		}
	};

	initialiserLocalisation();

	if (this.afficherSentiers) {
		this.getSentiers();
	}
});
