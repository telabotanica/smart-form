smartFormApp.directive('slideToggle', function($timeout) {
    return {
        restrict: 'A',
        link: function(scope, element, attrs) {
        	
            var target = angular.element(document.querySelector(attrs.slideToggle));
            target.wrap('<div class="slideable_content" style="margin:0 !important; padding:0 !important" ></div>');

        	attrs.expanded = (!attrs.expanded) ? true : attrs.expanded;
        	// la durée de l'animation est exprimée en millisecondes
        	attrs.animate = (!attrs.animate) ? false : attrs.animate;
            attrs.duration = (!attrs.duration) ? '1000' : attrs.duration;
            attrs.easing = (!attrs.easing) ? 'ease-in-out' : attrs.easing;
                        
            if(attrs.animate) {
                target.css({      
                    'transitionProperty': 'height',
                    'transitionDuration': attrs.duration+'ms',
                    'transitionTimingFunction': attrs.easing
                });
                
            	//TODO: wtf c'est moche mais on ne sait pas comment faire autrement !
                $timeout(function() {
                    // stockage et affectation de la hauteur véritable afin d'animer correctement la transition
                	// (sinon certains navigateurs refusent et cachent directement l'élément)
                	target.css({
                		'height': target[0].clientHeight+'px',
                	});
                }, 2000);
            }
            
        	element.find('span').removeClass("glyphicon-collapse-up"); 
            element.find('span').addClass("glyphicon-collapse-down");
            
            element.bind('click', function() {
            	attrs.expanded = (!attrs.expanded) ? false : attrs.expanded;
                if(!attrs.expanded) {
                    if(attrs.animate) {
	                	target.css({
	                        'height': target[0].scrollHeight+'px',
	                        'overflow': 'initial'
	                    });
	                	
	                	// Afin d'éviter que lors d'un changement de taille (par exemple un liste qui s'agrandit
	                	// les éléments s'affichent mal
	                    $timeout(function() {
	                    	target.css({
	                            'height': '100%'
	                        });
	                    }, attrs.duration * 1.1);
                    } else {
                    	target.css({
	                        'display': 'block'
	                    });
                    }
                } else {
                	 if(attrs.animate) {
	                	// enregistrement de la hauteur avant de la mettre à 0 (afin de pouvoir rendre 
	                	// à l'élément sa taille originale lors de l'opération contraire)
	                	target.attr('data-height', target[0].clientHeight);
	                	target.css({
	                        'height': '0px',
	                        'overflow': 'hidden'
	                    });
                	 } else {
                		 target.css({
 	                        'display': 'none'
 	                    });
                	 }
                }
                
            	element.find('span').toggleClass("glyphicon-collapse-up"); 
                element.find('span').toggleClass("glyphicon-collapse-down");
                attrs.expanded = !attrs.expanded;
            });
        }
    };
});