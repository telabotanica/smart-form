smartFormApp.controller('PaginationControleur', function () {

	this.pages = [];
	this.nbPages = 0;
	this.totalResultats = 0;
	this.taillePage = 20;
	this.pageCourante = 0;
	
	this.nomElementTrouve = "fiches";
	this.nomElementTrouveSingulier = "fiche trouvée"; 
	this.nomElementTrouvePluriel = "fiches trouvées";
		
	this.resetPagination = function() {
		this.pages = [];
		this.nbPages = 0;
		this.totalResultats = 0;
		this.taillePage = 20;
		this.pageCourante = 0;
	};
	
	this.construireNbPages = function(paginationResultats) {
		this.totalResultats = paginationResultats.total;
		this.nbPages = Math.ceil(this.totalResultats/this.taillePage);
		this.pages = [];
		
		var intervalleAvantApres = 6;
		// Cas où l'on affiche toutes les pages sans se prendre la tête
		if(this.nbPages <= 2*intervalleAvantApres) {
			for(var i = 0; i < this.nbPages; i++) {
				this.pages.push(i+1);	
			}
		} else {						
			var debutIntervalleGauche = Math.max(1, this.pageCourante - intervalleAvantApres);
			var finIntervalleGauche = this.pageCourante;
			
			var debutIntervalleDroite = finIntervalleGauche + 1;
			var finIntervalleDroite = Math.min(this.pageCourante + intervalleAvantApres, this.nbPages - 2);
			
			// Si on est au début de la liste et qu'on a moins de pages à gauche qu'à droite on en rajoute 
			// à droite 
			var decalageADroite = this.pageCourante - (debutIntervalleGauche);
			if(decalageADroite < intervalleAvantApres) {
				finIntervalleDroite = finIntervalleDroite + (intervalleAvantApres - decalageADroite) - 1;
				finIntervalleDroite = Math.min(finIntervalleDroite, this.nbPages - 2);
			}
			
			// Si on est à la fin de la liste et qu'on a moins de pages à droite qu'à gauche on en rajoute 
			// à gauche 
			var decalageAGauche = finIntervalleDroite - this.pageCourante;
			if(decalageAGauche < intervalleAvantApres) {
				debutIntervalleGauche = debutIntervalleGauche - (intervalleAvantApres - decalageAGauche) + 1;
				debutIntervalleGauche = Math.max(debutIntervalleGauche, 0);
			}
			
			// page de début obligatoire
			this.pages.push(1);
			
			if(this.pageCourante - intervalleAvantApres > 0) {
				this.pages.push("...");
			}
			
			for(var i = debutIntervalleGauche; i <= finIntervalleGauche; i++) {
				this.pages.push(i+1);
			}
						
			for(var i = debutIntervalleDroite; i <= finIntervalleDroite; i++) {
				this.pages.push(i+1);
			}
			
			if(this.pageCourante + intervalleAvantApres < this.nbPages - 1) {
				this.pages.push("...");
			}
			
			if(this.pageCourante < this.nbPages - 1) {
				// page de fin obligatoire si non incluse par la boucle précédente
				this.pages.push(this.nbPages);
			}
		}

	};
	
	this.getNbPages = function() {
		return this.nbPages; 
	};
	
	this.pagePrecedente = function() {
		if(this.pageCourante != 0) {
			this.changerPage(this.pageCourante);
		}
	};
	
	this.pageSuivante = function() {
		if(this.pageCourante != this.nbPages - 1) {
			this.changerPage(this.pageCourante + 2);
		}
	};
	
	this.changerPage = function(page) {
		// Pas besoin de changer de page si on est déjà sur la page demandée
		// où si l'on a cliqué sur une case de remplissage
		if(this.pageCourante == page - 1 || page == '...') {
			return;
		}
		
		this.pageCourante = page - 1;
		if(page - 1 < 0) {
			this.pageCourante = 0;
		}
		
		if(page -1 > this.nbPages) {
			this.pageCourante = this.nbPages;
		}
		this.surChangementPage();
	};
	
	this.getBorneMinIntervalleAffiche = function() {
		return this.pageCourante * this.taillePage + 1;
	};
	
	this.getBorneMaxIntervalleAffiche = function() {
		return Math.min(this.totalResultats, (this.pageCourante+1) * this.taillePage);
	};
	
	// A surcharger par le controleur qui l'instancie
	this.surChangementPage = function() {};
});