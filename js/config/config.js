var config = {
		referentiels : ["BDTFX", "BDTXA", "ISFAN", "APD"],
		infos_referentiels : 
		{
			"BDTFX" : {
				label : "France métropolitaine",
				noms_vernaculaires : "nvjfl"
			}, 
			"BDTXA" : {
				label : "Antilles Françaises",
				noms_vernaculaires : "nva"
			}, 
			"ISFAN" : {
				label : "Afrique du Nord",
				noms_vernaculaires : null
			}, 
			"APD" : {
				label : "Afrique du centre et de l'ouest",
				noms_vernaculaires : null
			}
		},
		nom_fiche : 'SmartFlore{referentiel}nt{num_tax}',
		url_wikini : "http://www.tela-botanica.org/wikini/eFloreRedaction/wakka.php?wiki={tag}",
		url_service_pages : "http://localhost/smart-form/services/Pages.php",
		url_service_favoris : "http://localhost/smart-form/services/Favoris.php",
		url_service_sentiers : "http://localhost/smart-form/services/Sentiers.php",
		url_fiche_mobile : "http://www.tela-botanica.org/mobile:{referentiel}-nn-{num_nom}",
		url_qr_code : "http://www.tela-botanica.org/tmp/eflore_v5_cache/qrcode/{referentiel}-{num_nom}.png",
		url_section_wiki : "http://localhost/yeswiki/api/rest/0.5/pages/{pageTag}?txt.format={format}&txt.section.titre={sectionTitre}&txt.template=PageTaxonSmartFlore",
		sections_pages : ["Introduction","Comment la reconnaître ?","Son histoire","Ses usages","Écologie & habitat","Ce qu'il faut savoir...","Sources"],
		url_service_annuaire : "http://localhost/annuaire/jrest/utilisateur/{service}",
		url_inscription : "http://www.tela-botanica.org/page:inscription"
};