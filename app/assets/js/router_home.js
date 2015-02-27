zibaldoneApp.config(['$routeProvider', '$httpProvider', function($routeProvider, $httpProvider) {

    $routeProvider.
    when('/', {
        templateUrl: 'partials/home.html',
        controller: 'booksCtrl'
    }).
	when('/search', {
        templateUrl: 'partials/search.html',
        controller: 'tagsCtrl'
    });

    $httpProvider.interceptors.push('customInterceptor');
}]);