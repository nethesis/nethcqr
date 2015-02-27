<?php
//PHPLICENSE
$table = new CI_Table;
$table->set_template(array('table_open' => '<table class="alt_table NethCQREntries" id="nethcqr_entries">'));
//build header
$h = array();
foreach($headers as $mod => $header) {
	$h += $header;
}
$table->set_heading($h);
$count = 0;
if (!$entries){
	$entries=array(array(
		'position' => 1,
		'condition' => '',
		'goto' => ''
	));
}
foreach ($entries as $e) {
	$count++;
	//position
        $row[] = form_input(
		array( 	'name' => 'entries[position][]', 
			'value' => $e['position'],
			'placeholder' => _('Position')
			));
	//condition
	$row[] = form_input(
                array(  'name' => 'entries[condition][]',
                        'value' => $e['condition'],
			'placeholder' => _('Condition')
                        ));
	//add destination. The last one gets a different count so that we can manipualte it on the page
	if ($count == count($entries)) {
		$row[] = drawselects($e['destination'], 'DESTID', false, false) . form_hidden('entries[goto][]', '');
	} else {
		$row[] = drawselects($e['destination'], $count, false, false) . form_hidden('entries[goto][]', '');
	}
	//delete buttom
	$row[] = '<img src="images/trash.png" style="cursor:pointer" title="' 
	. _('Delete this destination. Dont forget to click Submit to save changes!') 
	. '" class="delete_entrie">';
	//add module hooks	
	if (isset($d['hooks']) && $d['hooks']) {
		foreach ($d['hooks'] as $module => $hooks) {
			foreach ($hooks as $h) {
				$row[] = $h;
			}
		}
	}
	$table->add_row(array_values($row));	
	unset($row);
}

$ret = '';
$ret .= $table->generate();
$ret .= '<img class="NethCQREntries" src="modules/nethcqr/assets/images/add.png" style="cursor:pointer" title="' . _('Add Entry') 
		. '" id="add_entrie">';


echo $ret;
?>
