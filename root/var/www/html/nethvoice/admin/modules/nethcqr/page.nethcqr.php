<?php 
//PHPLICENSE 
$get_vars = array(
	'action' => '',
	'id_cqr' => '',
	'display' => ''
);
foreach ($get_vars as $k => $v) {
        $var[$k] = isset($_REQUEST[$k]) ? $_REQUEST[$k] : $v;
        $$k = $var[$k];//todo: legacy support, needs to GO!
}

echo load_view(dirname(__FILE__) . '/views/rnav.php', array('cqr_results' => nethcqr_get_details()) + $var);

if (!$action && !$id_cqr) {
?>
<h2><?php echo _("CQR"); ?></h2>
<br/><br/>
<a href="config.php?type=setup&display=nethcqr&action=add">
        <input type="button" value="Add a new CQR" id="new_dir">
</a>
<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>
<br/><br/><br/><br/><br/><br/><br/>

<?php
}


?>















