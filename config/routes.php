<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Área do cliente: http://localhost/perfex/arenagamer
 * (Staff continua em admin/arenagamer via controllers/Arenagamer.php)
 */
$route['arenagamer'] = 'client/index';
$route['arenagamer/(:any)'] = 'client/$1';
$route['arenagamer/(:any)/(:any)'] = 'client/$1/$2';
$route['arenagamer/(:any)/(:any)/(:any)'] = 'client/$1/$2/$3';
$route['arenagamer/(:any)/(:any)/(:any)/(:any)'] = 'client/$1/$2/$3/$4';
