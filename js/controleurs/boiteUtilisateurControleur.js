smartFormApp.controller('BoiteUtilisateurControleur', function ($scope, $rootScope, $interval, etatApplicationService) {

	this.utilisateur = {};
	this.urlInscription = config.url_inscription;

	this.message = '';
	this.erreur = false;
	this.courrielOK = false;
	this.mdpOK = false;

	// rafraîchissement périodique du jeton
	this.timer = null;

	// au cas où autre chose nous aurait connecté dans l'application
	$scope.$on('utilisateur.utilisateur-connecte', function(event, utilisateur) {
		this.utilisateur = utilisateur;
	});

	var lthis = this;

	/**
	 * Appelle annuaire:auth (SSO) pour obtenir un jeton contenant les
	 * données de l'utilisateur
	 */
	this.connecterUtilisateur = function() {
		if(this.formulaireValide()) {
			etatApplicationService.connecterUtilisateur(this.utilisateur,
			function(data) {
				// infos SSO
				etatApplicationService.jeton = data.token;
				etatApplicationService.idJeton = data.token_id;
				etatApplicationService.dureeJeton = data.duration;
				// infos utilisateur
				lthis.connaitreEtatUtilisateur();
				lthis.retirerEnErreur();
			},
			function(data) {
				lthis.mettreEnErreur();
			});
		} else {
			lthis.mettreEnErreur();
		}
	};

	this.verifierValiditeFormulaire = function() {
		this.courrielOK = this.courrielValide();
		this.mdpOK = this.mdpValide();

		if(this.formulaireValide()) {
			this.retirerEnErreur();
		}
	};

	this.formulaireValide = function() {
		return this.courrielValide() && this.mdpValide();
	}

	this.courrielValide = function() {
		return !!this.utilisateur.courriel && this.utilisateur.courriel != '';
	};

	this.mdpValide = function() {
		return !!this.utilisateur.mdp && this.utilisateur.mdp != '';
	};

	this.mettreEnErreur = function() {
		lthis.message = "Courriel / Mot de passe incorrect";
		this.erreur = true;
	};

	this.retirerEnErreur = function() {
		lthis.message = "";
		this.erreur = false;
	};

	/**
	 * Appelle l'annuaire pour connaître l'état de l'utilisateur; si celui-ci
	 * est connecté, rappelle l'annuaire avant l'expiration de jeton pour éviter
	 * cette dernière; si toutRecharger est false, ne fera que rafraîchir le
	 * jeton sans recharger les infos utilisateur (favoris, sentiers etc.)
	 */
	this.connaitreEtatUtilisateur = function(toutRecharger) {
		if (toutRecharger === undefined) {
			toutRecharger = true;
		}
		etatApplicationService.connaitreEtatUtilisateur(
		function(data) {
			if(!!data && !!data.session) {
				// infos SSO
				etatApplicationService.jeton = data.token; // jeton rafraîchi
				etatApplicationService.dureeJeton = data.duration;
				// infos utilisateur, si on le force ou si on n'était pas connecté
				if (toutRecharger || ! lthis.utilisateur.connecte) {
					lthis.utilisateur = lthis.construireUtilisateurDepuisJeton(data.token);
					lthis.utilisateur.connecte = true;
					etatApplicationService.utilisateur = lthis.utilisateur;
					$rootScope.$broadcast('utilisateur.utilisateur-connecte', etatApplicationService.utilisateur);
				}
				// rafraîchissement automatique du jeton
				if (lthis.timer == null) {
					// petite précaution de 10% pour ne pas se faire biaiser
					var delai = Math.floor(etatApplicationService.dureeJeton * 0.9) * 1000;
					lthis.timer = $interval(function() {
						//console.log("C'est reparti pour un tour, Georgette !");
						lthis.connaitreEtatUtilisateur(false);
					}, delai);
				}
			}
		},
		function(data) {

		});
	};

	// déconnecte l'utilisateur et arrête le rafraîchissement du jeton
	// @TODO - devrait-on plutôt l'accélérer pour détecter une connexion depuis
	// un autre endroit ?
	this.deconnecterUtilisateur = function() {
		etatApplicationService.deconnecterUtilisateur(
		function(data) {
			// infos SSO
			etatApplicationService.jeton = data.token; // devrait être null
			etatApplicationService.dureeJeton = 0;
			// infos utilisateur
			lthis.initialiserUtilisateurVide();
			etatApplicationService.utilisateur = lthis.utilisateur;
			$rootScope.$broadcast('utilisateur.utilisateur-deconnecte');
			// annulation du rafraîchissement
			if (lthis.timer != null) {
				$interval.cancel(lthis.timer);
				// nullifier pour le test dans connaitreEtatUtilisateur()
				lthis.timer = null;
			}
		});
	};

	this.initialiserUtilisateurVide = function() {
		this.utilisateur = {};
		this.utilisateur.connecte = false;
		this.utilisateur.id = "";
		this.utilisateur.prenom = "";
		this.utilisateur.nom = "";
		this.utilisateur.intitule = "";
		this.utilisateur.courriel = "";
		this.utilisateur.nomWiki = ""; // homonymes possibles
		this.utilisateur.mdp = ""; // wtf ?
	};

	/**
	 * Recopie les infos d'un jeton JWT dans le "profil" utilisateur
	 */
	this.construireUtilisateurDepuisJeton = function(jeton) {
		var infos = this.decoderJeton(jeton);
		//console.log(infos);
		var utilisateur = {
			connecte: true,
			id: infos.id,
			prenom: infos.prenom,
			nom: infos.nom,
			intitule: infos.intitule,
			courriel: infos.sub,
			nomWiki: infos.nomWiki
		};

		return utilisateur;
	};

	/**
	 * Décodage à l'arrache d'un jeton JWT, ATTENTION CONSIDERE QUE LE
	 * JETON EST VALIDE, ne pas décoder n'importe quoi - pas trouvé de lib simple
	 * Si pb de cross-browser, tenter ceci : https://code.google.com/p/javascriptbase64/
	 * ou ceci : https://code.google.com/p/crypto-js
	 */
	this.decoderJeton = function(jeton) {
		parts = jeton.split('.');
		payload = parts[1];
		payload = atob(payload);
		payload = JSON.parse(payload, true);

		return payload;
	};

	// destruction du timer si on sort de l'appli
	$scope.$on("$destroy", function() {
		$interval.cancel(this.timer);
	});

	this.initialiserUtilisateurVide();
	this.connaitreEtatUtilisateur();
});
