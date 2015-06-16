zibaldoneApp.config(['$routeProvider', '$httpProvider', function($routeProvider, $httpProvider) {

    $routeProvider.
      when('/about', {
        templateUrl: 'about.html',
      }).
      when('/books', {
        templateUrl: 'books.html',
        controller: 'booksCtrl'
      }).
      when('/references', {
        templateUrl: 'references.html',
        controller: 'referencesCtrl'
      }).
      when('/fragments', {
        templateUrl: 'fragments.html',
        controller: 'fragmentsCtrl'
      }).
      when('/admin/fragments', {
        templateUrl: 'fragments.html',
        controller: 'fragmentsCtrl'
      }).
      when('/admin/books', {
        templateUrl: 'books.html',
        controller: 'booksCtrl'
      });

      // .
      // otherwise({
      //   redirectTo: 'http://zibaldone.derox/admin/#books',
      //   //templateUrl: 'books.html',
      //   //controller: 'booksCtrl'
      // });
      $httpProvider.interceptors.push('customInterceptor');

  }]);