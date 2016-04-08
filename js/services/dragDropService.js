smartFormApp.service('dragDropService', function() {

	var dragDropService = {};
	dragDropService.objet = {};

	dragDropService.surDebutDrag = function(objet) {
		console.log(objet);
		this.objet = objet;
	};

	dragDropService.surFinDrag = function(objet) {

	};
});
