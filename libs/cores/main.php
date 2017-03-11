<?php

/*******************************************************************************

 Main File

*******************************************************************************/

benchmark();

import('libs/cores/version.php');
import('libs/cores/rand.php');
import('libs/cores/regexp.php');
import('libs/cores/info.php');
import('libs/cores/db.php');
import('libs/cores/test.php');

bootstrap();

session();

database();

normalize();

routing();

if (LOGGING_GET) {
    logging('get');
}
if (LOGGING_POST && !empty($_POST)) {
    logging('post');
}
if (LOGGING_FILES && !empty($_FILES)) {
    logging('files');
}

switch ($_REQUEST['_mode']) {
    case 'info_php':
        info_php();
        break;
    case 'info_levis':
        info_levis();
        break;
    case 'db_admin':
        db_admin();
        break;
    case 'db_migrate':
        db_migrate();
        break;
    case 'db_scaffold':
        db_scaffold();
        break;
    case 'test_index':
        test_index();
        break;
    case 'test_exec':
        test_exec();
        break;
}

service();

model();

controller();

view();

benchmark('Complete');

exit;
