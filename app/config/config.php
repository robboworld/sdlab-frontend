<?

$config = array();

/* Config connection with backend service through sockets.*/
$config['socket']['path'] = '/run/sdlab.sock';

/* Configuration laboratory */
$config['lab']['name'] = 'DLab001';
$config['lab']['lang'] = 'ru';
$config['lab']['page_suffix'] = 'ScratchDuino';

return $config;