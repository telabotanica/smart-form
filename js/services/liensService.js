smartFormApp.service('liensService', function() {
	
	var liensService = {};
	
	liensService.ouvrirFicheMobile = function(fiche) {
		referentiel_fiche = ""+fiche.infos_taxon.referentiel;
		url_fiche = config.url_fiche_mobile.replace('{referentiel}', referentiel_fiche.toLowerCase());
		url_fiche = url_fiche.replace('{num_nom}', fiche.infos_taxon.num_nom);
		console.log(url_fiche);
		window.open(url_fiche);
	};
	
	liensService.ouvrirQrCode = function(fiche) {
		url = config.url_qr_code.replace('{referentiel}', fiche.infos_taxon.referentiel.toLowerCase());
		url = url.replace('{num_nom}', fiche.infos_taxon.num_nom);
		window.open(url);
	};
	
	liensService.ouvrirPageInscription = function() {
		window.open(config.url_inscription);
	};
	
	liensService.exporterFicheEnPdf = function(fiche, sentierTitre) {
		url = config.url_service_export.replace('{referentiel}', fiche.infos_taxon.referentiel.toLowerCase());
		url = url.replace('{type}', 'fiche');
		url = url.replace('{num_tax}', fiche.infos_taxon.num_taxonomique);
		url = url.replace('{sentierTitre}', sentierTitre);
		window.open(url);
	};
	
	return liensService;	
});