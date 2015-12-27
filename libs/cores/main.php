<?php

/*********************************************************************

 Main File

*********************************************************************/

import('libs/cores/version.php');
import('libs/cores/rand.php');
import('libs/cores/regexp.php');
import('libs/cores/info.php');
import('libs/cores/db.php');
import('libs/cores/test.php');

session();

database();

normalize();

routing();

if (DEBUG_LEVEL && regexp_match(DEBUG_ADDR, clientip())) {
	switch ($_REQUEST['mode']) {
		case 'info_php':
			info_php();
		case 'info_levis':
			info_levis();
		case 'db_admin':
			db_admin();
		case 'db_migrate':
			db_migrate();
		case 'db_scaffold':
			db_scaffold();
		case 'test_index':
			test_index();
		case 'test_exec':
			test_exec();
	}
}

model();

controller();

view();

exit;
