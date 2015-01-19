'use strict';

/* App Module */

var zibaldoneApp = angular.module('zibaldoneApp', [
  'ngRoute',
  'ngAnimate',
  'ui.bootstrap',
  'ngResource',
  'ngMessages',
  'ui.sortable',
  'ngCookies'
]);

zibaldoneApp.constant('API_URL', 'http://zibaldone.derox/api');

// TODO
// zibaldoneApp.config(['$resourceProvider', function($resourceProvider) {
//   // Don't strip trailing slashes from calculated URLs
//   $resourceProvider.defaults.stripTrailingSlashes = false;
// }]);

// TODO
// zibaldoneApp.config(['$locationProvider', function($locationProvider) {
//   // use the HTML5 History API
//   $locationProvider.html5Mode(true);
// }]);

zibaldoneApp.config(['$routeProvider', '$httpProvider', function($routeProvider, $httpProvider) {

    $routeProvider.
      when('/books', {
        templateUrl: 'partials/books.html',
        controller: 'booksCtrl'
      }).
      when('/references', {
        templateUrl: 'partials/references.html',
        controller: 'referencesCtrl'
      }).
      when('/fragments', {
        templateUrl: 'partials/fragments.html',
        controller: 'fragmentsCtrl'
      }).
      otherwise({
        redirectTo: '/',
        templateUrl: 'partials/home.html',
      });

      $httpProvider.interceptors.push('customInterceptor');

  }]);
