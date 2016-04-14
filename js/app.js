smartFormApp = angular.module('smartFormApp', ['ngSanitize', 'ui.bootstrap', 'leaflet-directive', 'geolocation'])
.config(['$httpProvider', '$logProvider', function($httpProvider, $logProvider) {
	$httpProvider.defaults.withCredentials = true;

  	$logProvider.debugEnabled(false);

}]);
