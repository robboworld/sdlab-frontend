<?

$config = array();

/* Настройки взаимодействия с бэкендом через сокет.*/
$config['socket']['path'] = '/run/sdlab.sock';

/* Настройки лаборатории*/
$config['lab']['name'] = 'DLab001';
$config['lab']['lang'] = 'ru';
$config['lab']['page_suffix'] = 'ScratchDuino';

return $config;