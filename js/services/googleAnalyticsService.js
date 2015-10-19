smartFormApp.service('googleAnalyticsService', function() {
	
	var googleAnalyticsService = {};
	
	googleAnalyticsService.init = function() {
		// chargement de Google Analytics uniquement si on est en prod
		if (config.prod) {
			//console.log("init GA");

			(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

			ga('create', 'UA-57885-5', 'auto');
		}
	};
	
	googleAnalyticsService.envoyerPageVue = function() {
		if (config.prod) {
			//console.log("envoi GA pageview");
			ga('send', 'pageview');
		}
	};

	googleAnalyticsService.envoyerEvenement = function(categorie, action, label, nombre) {
		if (config.prod) {
			if (label == undefined) label = "";
			if (nombre == undefined) nombre = 1;
			//console.log("Envoi evt GA : " + categorie + ", " + action + ", " + label + ", " + nombre);
			ga('send', 'event', categorie, action, label, nombre);
		}
	};

	return googleAnalyticsService;
});