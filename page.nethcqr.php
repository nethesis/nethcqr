<?php
echo '<h1>Call Query Routing</h1>';
echo '<h2>Test Page<h2>';
$cqr_list=cqr_list();
foreach ($cqr_list as $cqr_entry)
	{
	echo 'id: '. $cqr_entry['id'].'<br />'."\n";
        echo 'name: '. $cqr_entry['name'].'<br />'."\n";
        echo 'description: ' . $cqr_entry['description'].'<br />'."\n";
        echo 'db_type: '. $cqr_entry['db_type'].'<br />'."\n";
        echo 'db_url: '. $cqr_entry['db_url'].'<br />'."\n";
        echo 'db_name: '. $cqr_entry['db_name'].'<br />'."\n";
        echo 'db_user: '. $cqr_entry['db_user'].'<br />'."\n";
        echo 'db_pass: '. $cqr_entry['db_pass'].'<br />'."\n";
        echo 'query: '.$cqr_entry['db_query'].'<br />'."\n";
	}
$to_add = array(
'name' => 'dummy name',
'description' => 'fake description',
'db_type' => 'mysql2',
'db_url' => '127.0.0.1',
'db_name' => 'asd',
'db_user' => 'root',
'db_pass' => 'nonlaso',
'query' => 'SELECT * FROM phonebook' 
);
cqr_add($to_add);
cqr_del(2);
cqr_del(3);
cqr_del(4);
cqr_del(5);
cqr_del(6);

?>
