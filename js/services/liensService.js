smartFormApp.service('liensService', function() {

	var liensService = {};

	liensService.lienFicheMobile = function(fiche) {
		referentiel_fiche = ""+fiche.infos_taxon.referentiel;
		url_fiche = config.url_fiche_mobile.replace('{referentiel}', referentiel_fiche.toLowerCase());
		url_fiche = url_fiche.replace('{num_nom}', fiche.infos_taxon.num_nom);
		//console.log(url_fiche);
		return url_fiche;
	};

	liensService.lienQrCode = function(fiche) {
		url = config.url_qr_code.replace('{referentiel}', fiche.infos_taxon.referentiel.toLowerCase());
		url = url.replace('{num_nom}', fiche.infos_taxon.num_nom);
		return url;
	};

	liensService.exporterFicheEnPdf = function(fiche, sentierTitre) {
		url = config.url_service_export.replace('{referentiel}', fiche.infos_taxon.referentiel.toLowerCase());
		url = url.replace('{type}', 'fiche');
		url = url.replace('{num_tax}', fiche.infos_taxon.num_taxonomique);
		url = url.replace('{sentierTitre}', sentierTitre);
		return url;
	};

	liensService.exporterSentierEnPdf = function(sentierTitre) {
		url = config.url_service_export.replace('{referentiel}', '');
		url = url.replace('{type}', 'sentier');
		url = url.replace('{num_tax}', '');
		url = url.replace('{sentierTitre}', sentierTitre);
		return url;
	};

	liensService.exporterSentiersEnCsv = function() {
		url = config.url_service_export.replace('{referentiel}', '');
		url = url.replace('{type}', 'sentiersCSV');
		url = url.replace('{num_tax}', '');
		url = url.replace('{sentierTitre}', '');
		return url;
	};

	return liensService;
});
