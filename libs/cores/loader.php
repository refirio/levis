<?php

/*********************************************************************

 Loader File

*********************************************************************/

require_once MAIN_PATH . 'config.php';
require_once MAIN_PATH . MAIN_LIBRARY_PATH . 'libs/cores/basis.php';

import('libs/cores/version.php');
import('libs/cores/rand.php');
import('libs/cores/regexp.php');
import('libs/cores/db.php');

session();

database();

normalize();

routing();
