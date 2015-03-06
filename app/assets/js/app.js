'use strict';

/* App Module */

var zibaldoneApp = angular.module('zibaldoneApp', [
  'ngRoute',
  'ngAnimate',
  'ui.bootstrap',
  'ngResource',
  'ngMessages',
  'ui.sortable',
  'ngCookies',
]);

zibaldoneApp.constant('API_URL', '/api');

// TODO
// zibaldoneApp.config(['$resourceProvider', function($resourceProvider) {
//   // Don't strip trailing slashes from calculated URLs
//   $resourceProvider.defaults.stripTrailingSlashes = false;
// }]);

// TODO
zibaldoneApp.config(['$locationProvider', function($locationProvider) {
  // use the HTML5 History API
  $locationProvider.html5Mode(true);
}]);

