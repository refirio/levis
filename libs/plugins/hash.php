<?php

/*********************************************************************

 Functions for Hash

*********************************************************************/

import('libs/cores/rand.php');

function hash_salt()
{
	return rand_string();
}

function hash_crypt($data, $salt, $count = 10000)
{
	for ($i = 0; $i < $count; $i++) {
		$data = md5($data . ':' . $salt);
	}
	return $data;
}
