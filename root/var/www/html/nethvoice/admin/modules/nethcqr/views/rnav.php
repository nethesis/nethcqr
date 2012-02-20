<?php

$li[] = '<a href="config.php?display='. urlencode($display) . '&action=add">' . _("Add CQR") . '</a>';

if (isset($cqr_results)){
	foreach ($cqr_results as $r) {
		$r['name'] = $r['name'] ? $r['name'] : 'CQR ID: ' . $r['id_cqr'];
		$li[] = '<a id="' . ( $id_cqr == $r['id_cqr'] ? 'current' : '') 
			. '" href="config.php?display=nethcqr&amp;action=edit&amp;id_cqr=' 
			. $r['id_cqr'] . '">' 
			. $r['name'] .'</a>';
	}
}	

echo '<div class="rnav">' . ul($li) . '</div>';
?>
