<?php

	function splitNt($page) {
		$page = str_replace('SmartFlore', '', $page);
		return split('nt', $page);
	}
	
	$config = parse_ini_file('config.ini', true);

	try {
		$dbh = new PDO('mysql:host='.$config['bdd']['host'].';dbname='.$config['bdd']['db'],$config['bdd']['user'], $config['bdd']['pass']);
	}
	catch (PDOException $e) {
		$error_message = $e->getMessage();
		echo "this is displayed because an error was found";
		exit();
	}
	
	$tpl_nom_page = !empty($_GET['tpl_nom_page']) ? $_GET['tpl_nom_page'] : 'SmartFlore%nt%';
	$tpl_nom_page = $dbh->quote($tpl_nom_page);
	
	$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	$dbh->exec("SET CHARACTER SET utf8");
	
	$champs = "id, tag, time, owner, user, latest";
	
	$requete = 'SELECT '.$champs.', COUNT(tag) as nb_revisions '.
				'FROM `eFloreRedaction_pages` '.
				'WHERE tag LIKE '.$tpl_nom_page.' '.
				'GROUP BY tag '.
				'ORDER BY nb_revisions DESC';
	
	$res = $dbh->query($requete);
	$res = $res->fetchAll(PDO::FETCH_ASSOC);
	
	$infos_indexees_par_nt = array();
	$nts = array();
	foreach($res as $resultat) {
		list($referentiel, $nt) = splitNt($resultat['tag']);
		if(empty($nts)) {
			$nts[$referentiel] = array();
		}
		$nts[$referentiel][] = $nt;
		$infos_indexees_par_nt[$referentiel.$nt] = $resultat;
	}
	
	$url_eflore_tpl = $config['eflore']['infos_taxons_url'];
	
	foreach($nts as $referentiel => $nts_a_ref) {
		if(!empty($referentiel)) {
			$nts_ref_tranches = array_chunk($nts_a_ref, 99, true);
			foreach($nts_ref_tranches as $tranche) {
				
				$url = sprintf($url_eflore_tpl, strtolower($referentiel), implode(',', $tranche));
				$infos = file_get_contents($url);
				$infos = json_decode($infos, true);
				
				foreach($infos['resultat'] as $num_nom => $infos_a_nt) {
					$infos_a_nt['num_nom'] = $num_nom;
					$infos_a_nt['referentiel'] = $referentiel;
					$infos_indexees_par_nt[$referentiel.$infos_a_nt['num_taxonomique']]['infos_taxon'] = $infos_a_nt;
				}
			}
		}
	}
	
	$dbh = null;
	
	header('Content-type: application/json');
	echo json_encode(array_values($infos_indexees_par_nt));
	exit;
?>