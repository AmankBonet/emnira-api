<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Halaman entri rekam medis per kunjungan pasien.
$route['web/rm/(:num)'] = 'web/rm_detail/index/$1';
$route['web/rm/(:num)/(save_gpa|save_riwayat|save_kondisi|save_vital|save_diagnosis|save_stabilisasi|kirim|batal_kirim|pdf)'] = 'web/rm_detail/$2/$1';
$route['web/rm/(:num)/delete_vital/(:num)'] = 'web/rm_detail/delete_vital/$1/$2';

// Rujukan masuk untuk faskes tujuan.
$route['web/rujukan'] = 'web/rujukan_masuk/index';
$route['web/rujukan/(:num)'] = 'web/rujukan_masuk/detail/$1';
$route['web/rujukan/(:num)/(terima|tiba)'] = 'web/rujukan_masuk/$2/$1';

// Master data (administrator); kunci tabel didefinisikan di config/master.php.
$route['web/master'] = 'web/master/index';
$route['web/master/([a-z_]+)'] = 'web/master/tabel/$1';
$route['web/master/([a-z_]+)/save'] = 'web/master/save/$1';
$route['web/master/([a-z_]+)/delete/([A-Za-z0-9_]+)'] = 'web/master/delete/$1/$2';
