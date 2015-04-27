smartFormApp = angular.module('smartFormApp', ['ngSanitize']);

smartFormApp.config(function($logProvider){
    $logProvider.debugEnabled(true);
});