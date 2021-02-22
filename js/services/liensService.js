smartFormApp.service('liensService', function() {

	var liensService = {};

	liensService.lienFicheMobile = function(fiche, titre) {
		var sentierTitre = (typeof titre !== 'undefined') ? titre : false;
		referentiel_fiche = ""+fiche.infos_taxon.referentiel;
		url_fiche = config.url_fiche_mobile.replace('{referentiel}', referentiel_fiche.toLowerCase());
		url_fiche = url_fiche.replace('{num_nom}', fiche.infos_taxon.num_nom);
		if (sentierTitre) {
			url_fiche = url_fiche+'?sentier='+sentierTitre;
		}
		//console.log(url_fiche);
		return url_fiche;
	};

	liensService.lienFicheEflore = function(fiche) {
		infos_referentiel = config.referentiels.find( ref => ref.nom === fiche.infos_taxon.referentiel );
		fournisseur = infos_referentiel.fournisseur_fiches_especes;
		url = config.fiches_especes[fournisseur].url.replace('{num_nom}', fiche.infos_taxon.num_nom);
		url = url.replace('{referentiel}', fiche.infos_taxon.referentiel.toLowerCase());
		return url;
	};

	liensService.lienQrCode = function(fiche) {
		url_fiche = this.lienFicheEflore(fiche);
		url = config.url_qr_code.replace('{nom_sci}', fiche.infos_taxon.nom_sci);
		url = url.replace('{lien_fiche}', url_fiche);
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
