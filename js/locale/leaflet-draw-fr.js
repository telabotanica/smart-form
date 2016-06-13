/**
 * Traduction française modifiée provenant de https://github.com/ThomasG77/Leaflet.draw/blob/f628fc8199fd8c874b8f631906fc1962ab9a2148/src/fr/Leaflet.draw.js
 */
L.drawLocal = {
	draw: {
		toolbar: {
			actions: {
				title: 'Annulez le dessin',
				text: 'Annuler'
			},
			buttons: {
				polyline: 'Dessiner une polyligne',
				polygon: 'Dessiner un polygone',
				rectangle: 'Dessiner un rectangle',
				circle: 'Dessiner un cercle',
				marker: 'Dessiner un marqueur'
			}
		},
		handlers: {
			circle: {
				tooltip: {
					start: 'Cliquez et déplacez pour dessiner un cercle.'
				}
			},
			marker: {
				tooltip: {
					start: 'Cliquez sur la carte pour placer un marqueur.'
				}
			},
			polygon: {
				tooltip: {
					start: 'Cliquez pour commencer à dessiner une forme.',
					cont: 'Cliquez pour continuer à dessiner une forme.',
					end: 'Cliquez sur le dernier point pour fermer cette forme.'
				}
			},
			polyline: {
				error: '<strong>Erreur:</strong> Les arrêtes de la forme ne doivent pas se croiser !',
				tooltip: {
					start: 'Cliquez pour commencer à tracer une ligne.',
					cont: 'Cliquez pour continuer le tracé.',
					end: 'Cliquez sur le dernier point pour terminer le tracé.'
				}
			},
			rectangle: {
				tooltip: {
					start: 'Cliquez et déplacez pour dessiner un rectangle.'
				}
			},
			simpleshape: {
				tooltip: {
					end: 'Relachez la souris pour finir de dessiner.'
				}
			}
		}
	},
	edit: {
		toolbar: {
			actions: {
				save: {
					title: 'Sauvegardez les changements.',
					text: 'Sauver'
				},
				cancel: {
					title: 'Annulez l\'édition, ignorer tous les changements.',
					text: 'Annuler'
				}
			},
			buttons: {
				edit: 'Editer les couches.',
				editDisabled: 'Pas de couches à éditer.',
				remove: 'Supprimer les couches.',
				removeDisabled: 'Pas de couches à supprimer.'
			}
		},
		handlers: {
			edit: {
				tooltip: {
					text: 'Déplacez les ancres, ou le marqueur pour éditer l\'objet.',
					subtext: 'Cliquez sur Annuler pour revenir sur les changements.'
				}
			},
			remove: {
				tooltip: {
					text: 'Cliquez sur l\'objet à enlever'
				}
			}
		}
	}
};
