<?php
/**
 * Config
 * Array with application configuration
 */
return array(

		/* Config connection with backend service through sockets.*/
		'socket' => array(
				'path'        => '/run/sdlab.sock',
		),

		/* Configuration laboratory */
		'lab'    => array(
				'name'        => 'DLab001',
				'lang'        => 'ru',
				'page_suffix' => 'ScratchDuino',
				'admin_key'   => '123456',
		),
);
