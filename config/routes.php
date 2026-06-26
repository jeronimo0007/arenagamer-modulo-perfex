<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Endpoint interno server-to-server (API Java -> Perfex) para criar faturas de
 * créditos. DEVE ficar ANTES das rotas catch-all abaixo: no MX, o placeholder
 * (:any) é convertido para ".+" e captura barras, então a primeira rota que
 * casar vence. Esta rota aponta para o controller Internal (sem login),
 * autenticado por segredo compartilhado.
 */
$route['arenagamer/internal/credit_invoice'] = 'internal/credit_invoice';

/**
 * Área do cliente: http://localhost/perfex/arenagamer
 * (Staff continua em admin/arenagamer via controllers/Arenagamer.php)
 */
$route['arenagamer'] = 'client/index';
$route['arenagamer/(:any)'] = 'client/$1';
$route['arenagamer/(:any)/(:any)'] = 'client/$1/$2';
$route['arenagamer/(:any)/(:any)/(:any)'] = 'client/$1/$2/$3';
$route['arenagamer/(:any)/(:any)/(:any)/(:any)'] = 'client/$1/$2/$3/$4';
