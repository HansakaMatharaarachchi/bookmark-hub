<?php
defined('BASEPATH') or exit('No direct script access allowed');

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



//API Routes V1

// Members
$route["api/v1/members"]["POST"] = "api/v1/members/index";
$route["api/v1/members/me"]["GET"] = "api/v1/members/me";
$route["api/v1/members/me"]["DELETE"] = "api/v1/members/me";

// Auth
$route["api/v1/auth/login"]["POST"] = "api/v1/auth/login";
$route["api/v1/auth/logout"]["POST"] = "api/v1/auth/logout";
$route["api/v1/auth/refresh"]["POST"] = "api/v1/auth/refresh";

// Bookmarks
$route["api/v1/bookmarks"]["GET"] = "api/v1/bookmarks/index";
$route["api/v1/bookmarks"]["POST"] = "api/v1/bookmarks/index";
$route["api/v1/bookmarks/(:num)"]["GET"] = "api/v1/bookmarks/show/$1";
$route["api/v1/bookmarks/(:num)"]["PUT"] = "api/v1/bookmarks/update/$1";
$route["api/v1/bookmarks/(:num)"]["DELETE"] = "api/v1/bookmarks/delete/$1";

// Force Strict Routing
// $route['api/v1/(:any)'] = "welcome";
