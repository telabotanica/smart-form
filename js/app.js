smartFormApp = angular.module('smartFormApp', ['ngSanitize', 'ui.bootstrap']);

smartFormApp.config(function($logProvider){
    $logProvider.debugEnabled(true);
});