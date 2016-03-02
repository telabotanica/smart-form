smartFormApp.directive('colonneCollapsible', function($timeout) {
    return {
        restrict: 'A',
        link: function(scope, element, attrs) {
        	// Directive restreinte aux tables
        	if (element[0].tagName== 'TABLE') {

            	var entetes = element.find('tr').find('th');
            	var visible = (attrs.visible === "true") ? true : false;

            	// Ajout du bouton afficher / cacher au début de la première ligne première colonne
            	var elementEntete = angular.element(entetes[0]);
        		var htmlEntete = elementEntete.html();
				var htmlCollapser = '<button type="button" class="btn btn-default btn-sm colonne-collapser" title="Afficher/Cacher les infomations supplémentaires">'+
										'<span class="glyphicon" aria-hidden="true"></span>'+
									'</button>';
				elementEntete.html(htmlEntete+htmlCollapser);

        		var collapser = angular.element(entetes[0].querySelector('.colonne-collapser'));
        		if(visible) {
        			collapser.find('span').removeClass("glyphicon-plus");
        			collapser.find('span').addClass("glyphicon-minus");
        		} else {
        			collapser.find('span').removeClass("glyphicon-minus");
        			collapser.find('span').addClass("glyphicon-plus");
        			element.addClass('colonnes-collapsed');
        		}

        		collapser.bind('click', function() {
        			var collapser = angular.element(entetes[0].querySelector('.colonne-collapser'));
            		collapser.find('span').toggleClass("glyphicon-plus");
            		collapser.find('span').toggleClass("glyphicon-minus");
            		element.toggleClass('colonnes-collapsed');
        		});
            }
        }
    };
});
