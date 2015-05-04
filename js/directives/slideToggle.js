smartFormApp.directive('slideToggle', function($timeout) {
    return {
        restrict: 'A',
        link: function(scope, element, attrs) {
        	
            var target = angular.element(document.querySelector(attrs.slideToggle));
            target.wrap('<div class="slideable_content" style="margin:0 !important; padding:0 !important" ></div>');

            // default properties
        	attrs.expanded = (!attrs.expanded) ? true : attrs.expanded;
            attrs.duration = (!attrs.duration) ? '1s' : attrs.duration;
            attrs.easing = (!attrs.easing) ? 'ease-in-out' : attrs.easing;
            
            target.css({
                'overflow': 'hidden',       
                'transitionProperty': 'height',
                'transitionDuration': attrs.duration,
                'transitionTimingFunction': attrs.easing
            });
            
        	//TODO: wtf c'est moche mais on ne sait pas comment faire autrement !
            $timeout(function() {
                // stockage et affectation de la hauteur véritable afin d'animer correctement la transition
            	// (sinon certains navigateurs refusent et cachent directement l'élément)
            	target.css({
            		'height': target[0].clientHeight+'px',
            	});
            	target.attr('data-height', target[0].clientHeight);
            }, 2000);
            
        	element.find('span').removeClass("glyphicon-collapse-up"); 
            element.find('span').addClass("glyphicon-collapse-down");
            
            element.bind('click', function() {
            	attrs.expanded = (!attrs.expanded) ? false : attrs.expanded;
                if(!attrs.expanded) {
                    var y = target.attr('data-height');
                	target.css({
                        'height': y + 'px'
                    });
                	element.find('span').removeClass("glyphicon-collapse-up"); 
                    element.find('span').addClass("glyphicon-collapse-down");
                } else {
                	// enregistrement de la hauteur avant de la mettre à 0 (afin de pouvoir rendre 
                	// à l'élément sa taille originale lors de l'opération contraire)
                	target.attr('data-height', target[0].clientHeight);
                	target.css({
                        'height': '0px'
                    });
                	element.find('span').removeClass("glyphicon-collapse-down");
                    element.find('span').addClass("glyphicon-collapse-up");
                }
                attrs.expanded = !attrs.expanded;
            });
        }
    };
});