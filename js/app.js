smartFormApp = angular.module('smartFormApp', ['ngSanitize', 'ui.bootstrap', 'leaflet-directive', 'geolocation'])
.config(['$httpProvider', function($httpProvider) {
	$httpProvider.defaults.withCredentials = true;
}]);

smartFormApp.config(function($logProvider){
    $logProvider.debugEnabled(true);
});
