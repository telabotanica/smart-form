smartFormApp.controller('SentiersControleur', function ($scope, $rootScope, $window, $http, smartFormService, etatApplicationService, liensService, googleAnalyticsService, geolocation, leafletData) {

	this.sentiers = [];
	this.sentierSelectionne = creerObjetSentierVide();

	this.nouveauSentierTitre = "";

	this.afficherSentiers = etatApplicationService.utilisateur.connecte;
	this.utilisateurNomWiki = etatApplicationService.utilisateur.nomWiki;

	this.liensService = liensService;
	this.chargementSentier = false;

	this.caracteresInterdits = ';/?:@=&';

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
		};
	}

	enrichirSentierLabel = function(sentiers) {
		sentiers.forEach(function(sentier, key, sentiers) {
			if (sentier.auteur !== lthis.utilisateurNomWiki) {
				sentiers[key].label = sentier.titre + ' (' + sentier.auteur + ')';
			} else {
				sentiers[key].label = sentier.titre;
			}
		});
	};

	this.editerFiche = function(fiche) {
		$rootScope.$broadcast('edition.editer-fiche', fiche);
	};

	this.surChangementSentier = function() {
		this.chargementSentier = true;
		smartFormService.getFichesASentier(this.sentierSelectionne.titre,
			function(data) {
				lthis.sentierSelectionne.fiches = data.resultats;
				lthis.chargementSentier = false;
			},
			function(data) {
				console.log('C\'est pas bon !');
			}
		);

		smartFormService.getLocalisationASentier(this.sentierSelectionne.titre,
			function(data) {
				lthis.sentierSelectionne.localisation = data.localisation;
				lthis.sentierSelectionne.dessin = data.dessin;
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
				enrichirSentierLabel(lthis.sentiers);
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
		if (this.nomSentierValide()) {
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
		var nouveauSentier = creerObjetSentierVide(),
			now = Math.round(new Date().getTime() / 1000);
		nouveauSentier.titre = titre;
		nouveauSentier.auteur = lthis.utilisateurNomWiki;
		nouveauSentier.dateCreation = now;
		nouveauSentier.dateDerniereModif = now;
		this.sentiers.push(nouveauSentier);
		this.sentierSelectionne = this.sentiers[this.sentiers.length - 1];
	};

	this.nomSentierValide = function() {
		if (this.nouveauSentierTitre.match(new RegExp('[' + this.caracteresInterdits + ']'))) {
			window.alert('Le nom du sentier ne doit pas contenir les caractères suivants ' + this.caracteresInterdits);
			return false;
		} else if (!this.nouveauSentierTitre) {
			window.alert('Le nom du sentier ne doit pas être vide');
			return false;
		} else if (this.contientSentier(this.nouveauSentierTitre)) {
			window.alert('Un sentier du même nom existe déjà');
			return false;
		}

		return true;
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

	function initialiserLeafletConfig() {
		// layer du tracé du sentier (on doit surement charger un tracé existant là dedans, à base de fromGeoJson ou un truc du genre tavu)
		lthis.editableLayers = new L.FeatureGroup();

		// création de l'event pour recup le tracé
		leafletData.getMap().then(function(map) {
			map.addLayer(lthis.editableLayers);

			leafletData.getLayers().then(function(baselayers) {
				map.on('draw:created', function(e) {
					lthis.editableLayers.addLayer(e.layer);
					lthis.dessinSentier = e.layer;
				});
			});
		});

		lthis.baselayers = {
			osm: {
				url: 'http://osm.tela-botanica.org/tuiles/osmfr/{z}/{x}/{y}.png',
				layerOptions: {
	                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
					maxZoom: 20
	            },
	            name: 'osm',
                type: 'xyz',
                layerParams: {
                	'showOnSelector': false
                }
			},
			gmaps: {
				url: 'http://mt1.google.com/vt/lyrs=y@218131653&hl=fr&src=app&x={x}&y={y}&z={z}',
				layerOptions: {
					attribution: 'Map data &copy;'+new Date().getFullYear()+' <a href="http://maps.google.com">Google</a>',
					maxZoom: 21
				},
				name: 'gmaps',
            	type: 'xyz',
                layerParams: {
                	'showOnSelector': false
                }
			}
		};

		// Initialisation du centre du leaflet (sur paris)
		lthis.leafletCenter = {
			lat: 48.856614,
			lng: 2.3522219,
			zoom: 14
		};

		// Config du leaflet
		lthis.leafletConfig = {
			layers: {
				baselayers: {
					osm: lthis.baselayers.osm
				}
			},
			center: lthis.leafletCenter,
			markers: {}
		};
	}

	function purgerEtatLocalisation() {
		$("#modalet").modal('hide');
		lthis.editableLayers.removeLayer(lthis.dessinSentier);
		lthis.etape = '';

		initialiserLeafletConfig();
	}

	function initialiserLocalisation() {
		lthis.modeSuppressionMarkers = false;
		lthis.etape = 'localiser-sentier';
		lthis.titreModal = 'Cliquer pour placer le point d\'entrée du sentier';
		lthis.methodeLocalisation = 'auto';
		lthis.choixAdresses = [];
		lthis.dessinSentier = {}; // layer du leaflet contenant le polyline
		lthis.dessinSentierAvantModif = undefined; // copie avant changements en mode édition
		lthis.dessinModifie = false;

		// Initialisation des markers
		lthis.markers = {};
		lthis.compteurDeMarkers = {};

		// Chargement des données dans le cas de l'édition d'un sentier
		if (angular.isDefined(lthis.sentierSelectionne.localisation.individus)) {
			// Les individus
			angular.forEach(lthis.sentierSelectionne.localisation.individus, function(value, key) {
				var fiche = getSentierSelectionneFicheParTag(value.ficheTag),
					markerName = creerNomMarker(value.ficheTag)
				;

				lthis.markers[markerName] = creerMarkerIndividu(value, fiche.infos_taxon.nom_sci, value.ficheTag);
			});

			// Le centre de la carte sur le sentier
			angular.merge(lthis.leafletCenter, lthis.sentierSelectionne.localisation.sentier);
			lthis.leafletCenter.zoom = 18;
		}

		// En édition on ajoute à la config seulement le sentier pour la première étape
		if (angular.isDefined(lthis.sentierSelectionne.localisation.sentier)) {
			lthis.leafletConfig.markers = {
				sentier: creerMarkerSentier(lthis.sentierSelectionne.localisation.sentier)
			}
		}

		// mic-mac dégueu (et magique) pour éviter la zone de réglages inutiles en haut à droite du leaflet
		lthis.changerLayer('gmaps');
		lthis.changerLayer('osm');

		function getSentierSelectionneFicheParTag(tag) {
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

	/**
	 * Active le mode édition d'un layer
	 *
	 * Si il est affiché sur la carte alors des éléments de controles
	 * apparaitront pour pouvoir le modifier
	 *
	 * @param      {Object}  layer   Le layer à éditer
	 */
	function editerLayer(layer) {
		layer.options.editing || (layer.options.editing = {}); // (fix: https://github.com/Leaflet/Leaflet.draw/issues/364#issuecomment-74184767)
		layer.editing.enable();
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

	this.changerLayer = function(layer) {
		// obligé de détruire le précédent layer pour pouvoir en changer
		lthis.leafletConfig.layers.baselayers = {};
		lthis.leafletConfig.layers.baselayers[layer] = lthis.baselayers[layer];

		if (layer == 'osm' && lthis.leafletCenter.zoom > 20) {
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
			purgerEtatLocalisation();
		}
	};

	function creerCopieDuPolyline(layer) {
		var layerCoords = L.GeoJSON.coordsToLatLngs((layer.toGeoJSON()).geometry.coordinates);
		return L.polyline(layerCoords);
	}

	this.supprimerDessin = function() {
		if ($window.confirm('Recommencer à zéro ?')) {
			// suppresion du layer visible sur la carte
			lthis.editableLayers.removeLayer(lthis.dessinSentier);
			// suppression de la référence interne
			delete lthis.dessinSentier;

			lthis.dessinModifie = true;

			// réactive le mode dessin
			leafletData.getMap().then(function(map) {
				lthis.polylineDrawer = new L.Draw.Polyline(map);
				lthis.polylineDrawer.options.shapeOptions.opacity = 0.7;
				lthis.polylineDrawer.options.shapeOptions.weight = 8;

				lthis.polylineDrawer.enable();
			});
		}
	};

	this.revertChangementsDessin = function() {
		if (lthis.dessinSentierAvantModif) {
			// enlève le layer édité
			lthis.editableLayers.removeLayer(lthis.dessinSentier);
			// rétablie la référence interne
			lthis.dessinSentier = angular.copy(lthis.dessinSentierAvantModif);
			// réactive le mode édition du layer
			editerLayer(lthis.dessinSentier);
			// rajoute le layer sur la carte
			lthis.editableLayers.addLayer(lthis.dessinSentier);

			lthis.dessinModifie = false;
		} else {
			window.alert('rien à rétablir');
		}
	};

	// Étapes :
	// 1 : placer le point d'entrée du sentier, servant à localiser le sentier sur le territoire
	// 2 : placer les points des différents individus du sentier pour avoir des infos au cours de la balade
	// 3 : tracer le chemin indicatif à suivre au sein du sentier

	// FIN ÉTAPE 1, début étape 2
	this.validerLocalisationSentier = function() {
		// Restaure les markers d'individus dans le cas d'une édition du sentier
		angular.merge(lthis.leafletConfig.markers, lthis.markers);

		angular.merge(lthis.leafletConfig.markers.sentier, {
			draggable: false,
			opacity: 0.7,
			message: 'Entrée du sentier'
		});

		lthis.etape = 'localiser-individus';
		this.changerLayer('gmaps');
		lthis.titreModal = 'Cliquer sur une espèce pour créer un marqueur';

		// On décale légèrement le centre pour éviter le chevauchement des markers et on zoom
		angular.merge(lthis.leafletCenter, {
			lat: lthis.leafletConfig.markers.sentier.lat - 0.00005,
			lng: lthis.leafletConfig.markers.sentier.lng + 0.00005,
			zoom: 19
		});
	};

	// FIN ÉTAPE 2, début étape 3
	this.validerLocalisationIndividus = function() {
		lthis.etape = 'dessin-sentier';
		this.changerLayer('gmaps');
		lthis.titreModal = 'Tracez le parcours à suivre';

		// désactivation des autres markers
		angular.forEach(lthis.leafletConfig.markers, function(marker) {
			angular.merge(marker, {
				draggable: false,
				opacity: 0.7
			});
		});

		// active le mode dessin si le tracé n'existe pas, sinon on passe en mode édition
		leafletData.getMap().then(function(map) {
			if (lthis.sentierSelectionne.dessin) {
				var LatLng = L.GeoJSON.coordsToLatLngs(lthis.sentierSelectionne.dessin.coordinates);
				var loadedLayer = L.polyline(LatLng);
				// charge le layer du tracé
				lthis.editableLayers.addLayer(loadedLayer);
				lthis.dessinSentier = loadedLayer;
				// active le mode édition du layer
				editerLayer(loadedLayer);

				if (angular.isUndefined(lthis.dessinSentierAvantModif)) {
					// garde le tracé original pour pouvoir le restaurer
					lthis.dessinSentierAvantModif = creerCopieDuPolyline(lthis.dessinSentier);
				}

				map.on('draw:editvertex', function(e) {
					lthis.dessinModifie = true;
				});
			} else {
				lthis.polylineDrawer = new L.Draw.Polyline(map);
				lthis.polylineDrawer.options.shapeOptions.opacity = 0.7;
				lthis.polylineDrawer.options.shapeOptions.weight = 8;

				lthis.polylineDrawer.enable();
			}
		});
	};

	// FIN ÉTAPE 3, envoi des données
	this.terminerLocalisation = function() {
		// on copie les marqueurs
		var markers = angular.copy(lthis.leafletConfig.markers);
		// on prépare l'objet contenant les infos de localisation qui va être stocké
		var localisation = {
			sentier:  {
				lat: markers.sentier.lat,
				lng: markers.sentier.lng
			},
			individus: {}
		};
		var dessin = '';
		// prépare les infos du dessin du sentier
		if (lthis.dessinSentier) {
			dessin = (lthis.dessinSentier.toGeoJSON()).geometry;
		}
		// on enlève les infos du sentier de la copie
		delete markers.sentier;
		// on parcours les marqueurs qu'on ajoute aux infos de localisation
		angular.forEach(markers, function(marker, markerName) {
			localisation.individus[markerName] = {
				ficheTag: marker.ficheTag,
				lat: marker.lat,
				lng: marker.lng
			};
		});
		// on envoi les infos de localisation au stockage
		smartFormService.ajouterSentierLocalisation(
			lthis.sentierSelectionne.titre,
			localisation,
			dessin,
			function(data) {
				if (data == 'OK') {
					lthis.sentierSelectionne.localisation = localisation;
					lthis.sentierSelectionne.localisation.nbIndividus = Object.keys(localisation.individus).length;
					lthis.sentierSelectionne.dessin = dessin;
					// @todo: date de modification
					purgerEtatLocalisation();

					// stats
					googleAnalyticsService.envoyerEvenement('sentier', 'ajout-localisation', '{ "sentier": "' + sentier.titre + '" }');
					if (lthis.dessinSentier) {
						googleAnalyticsService.envoyerEvenement('sentier', 'ajout-dessin-sentier', '{ "sentier": "' + sentier.titre + '" }');
					}
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
			lthis.changerLayer('osm');
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

	initialiserLeafletConfig();

	if (this.afficherSentiers) {
		this.getSentiers();
	}
});
