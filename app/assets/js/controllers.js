'use strict';

/* Controllers */

zibaldoneApp.controller('navbarCtrl', ['$scope', '$location', function($scope, $location) {

    $scope.isActive = function(viewLocation) {
        return viewLocation === $location.path();
    };

}]);

zibaldoneApp.controller('AlertCtrl', ['$scope', 'Alerts', '$interval',
    function($scope, Alerts, $interval) {

        $scope.alerts = Alerts.list();

        $scope.closeAlert = function(index) {
            Alerts.delete(index);
        };

        //every 100 ms it checks if there are old alerts to be hidden
        $interval(function() {

            //marks the alerts older than 4s as hidden
            angular.forEach($scope.alerts, function(alertObj, index) {
                if ((Date.now() - alertObj.created) > 3000) {
                    Alerts.hide(index);
                }
            });

        }, 100);

    }
]);

zibaldoneApp.controller('ModalArticleCtrl', function($scope, $modalInstance, $sce, articleContent, articleTitle) {

    $scope.articleTitle = articleTitle;
    $scope.articleContent = $sce.trustAsHtml(articleContent);

    $scope.close = function() {
        $modalInstance.dismiss('cancel');
    };

});

zibaldoneApp.controller('articlesCtrl', ['$scope', 'ArticlesAPI', 'ArticleAPI', 'Alerts', '$modal', function($scope, ArticlesAPI, ArticleAPI, Alerts, $modal) {

    $scope.ArticlesAPI = ArticlesAPI.list();
    $scope.articleTitle = '';
    $scope.articleContent = '';

    $scope.open = function (size){

        var modalInstance = $modal.open({
            templateUrl: '/partials/article_modal.html',
            controller: 'ModalArticleCtrl',
            size: size,
            resolve: {
                articleTitle: function(){
                    return $scope.articleTitle;
                },
                articleContent: function(){
                    return $scope.articleContent;
                }
            }
        });
    };

    $scope.showArticle = function(articleId, title) {
        
        $scope.articleTitle = title;

        ArticleAPI.get({articleId: articleId}, function(response){
            $scope.articleContent = response.results.article;
            $scope.open('lg');
        });

    }

}]);

zibaldoneApp.controller('tagsCtrl', ['$scope', 'TagsAPI', 'Alerts', function($scope, TagsAPI, Alerts) {

    $scope.TagsAPI = TagsAPI.list();

}]);



zibaldoneApp.controller('booksCtrl', ['$scope', 'BooksAPI', 'BookAPI', 'Alerts', '$modal', function($scope, BooksAPI, BookAPI, Alerts, $modal) {

    $scope.BooksAPI = BooksAPI.list();

    $scope.refreshBookList = function(){
        $scope.BooksAPI = BooksAPI.list();
    }

    $scope.addBook = function() {

        if (!$scope.title) {
            Alerts.add('danger', 'Please write a title');
            return false;
        }

        BookAPI.add({title: $scope.title}, function(promise) {
            if (!promise.errors) {
                // pushes the returned json book in the books list.
                // this avoids a new BooksAPI.list() call
                $scope.BooksAPI.results.books.push(JSON.parse(promise.results.book));
                $scope.title = ''; //cleans the form field
            }
        });

    }

    $scope.deleteBook = function(bookId) {

        BookAPI.delete({id: bookId}, function(promise) {
            if (!promise.errors) {
                angular.forEach($scope.BooksAPI.results.books, function(book, index) {
                    if (book.id === bookId) {
                        // removes the book item from the books list.
                        // this avoids a new BooksAPI.list() call
                        $scope.BooksAPI.results.books.splice(index,1);
                    }
                });
            }
        });
    }

    $scope.updateBook = function(book){
        BookAPI.update({book: book}, function(promise) {
            if (!promise.error) {
                return 0; //closes the form
            }
            return 1;
        });
    }

    //TODO I need to improve this whole thing
    $scope.showBook = function (template){
        var modalInstance = $modal.open({
            templateUrl: template,
            size: 'lg',
        });
    };
}]);



zibaldoneApp.controller('referencesCtrl', ['$scope', 'BooksAPI', 'ReferencesAPI', 'ReferenceAPI', 'Alerts', '$cookieStore', function($scope, BooksAPI, ReferencesAPI, ReferenceAPI, Alerts, $cookieStore) {

    //TODO this cookie thing should be a service

    function selectBook(bookId,bookListIndex) {

        $scope.selectedBook = {
            'bookId': bookId,
            'bookListIndex': bookListIndex
        };

        // stores the values in the cookie
        $cookieStore.put('selectedBook', $scope.selectedBook);

        // selects the proper tab
        $scope.BooksAPI.results.books[bookListIndex].active = true;

        // performs an API request to get the references list
        $scope.ReferencesAPI = ReferencesAPI.list({bookId:bookId});
    }


    // Defaults
    $scope.ReferencesAPI = {};
    $scope.newReference = {}; // this is the New Reference form object
    $scope.newReference.subref = 'GithubReference';
    $scope.newReference.synchrony = false;

    // tries to retrieve a previously stored cookie
    $scope.selectedBook = $cookieStore.get('selectedBook');

    // performs a query to the API to get the book list
    $scope.BooksAPI = BooksAPI.list({}, function(promise){

        // if the query is successfull and there is at least on item in the book list
        if (!promise.errors && promise.results.books[0]) {

            // if a cookie has been found
            if ($scope.selectedBook.bookId) {

                // and if the value stored in the cookie matches an existent book
                angular.forEach(promise.results.books, function(book, index) {
                    if (book.id === $scope.selectedBook.bookId) {
                        selectBook(book.id, index);
                    }
                });

            } else {

                // I select the 1st book in the booklist (default)
                selectBook(promise.results.books[0].id, 0);

            }
        }
    });


    // this function is activated when the user clicks on one of the vertical tabs
    $scope.selectThisBook = function (bookId,bookListIndex) {
        selectBook(bookId,bookListIndex);
    }

    $scope.addReference = function(){

        Alerts.add('info', 'Download started. Please wait ...');
        $scope.newReference.book_id = $scope.selectedBook.bookId;

        ReferenceAPI.add($scope.newReference, function(promise){
            if (!promise.errors) {
                Alerts.add('success', 'Reference added and downloaded');
                // pushes the returned json reference in the references list.
                // this avoids a new ReferencesAPI.list() call
                $scope.ReferencesAPI.results.references.push(JSON.parse(promise.results.reference));
                $scope.clearReferenceForm();
            }
        });
    }

    $scope.deleteReference = function(referenceId) {
        ReferenceAPI.delete({referenceId:referenceId}, function(promise){
            if (!promise.errors) {
                angular.forEach($scope.ReferencesAPI.results.references, function(reference, index) {
                    if (reference.id === referenceId) {
                        // removes the reference item from the references list.
                        // this avoids a new referencesAPI.list() call
                        $scope.ReferencesAPI.results.references.splice(index,1);
                    }
                });
            }
        });
    }

    $scope.clearReferenceForm = function() {
        $scope.newReference = {};
        $scope.newReference.subref = 'GithubReference';
        $scope.newReference.synchrony = false;
        //$scope.newReference.book_id = $scope.selectedBook.id;
    }

}]);


zibaldoneApp.controller('fragmentsCtrl', ['$scope', 'BooksAPI', 'BookAPI', 'FragmentsAPI', 'FragmentAPI', 'Alerts', '$cookieStore', function($scope, BooksAPI, BookAPI, FragmentsAPI, FragmentAPI, Alerts, $cookieStore) {

    //TODO this cookie thing should be a service

    function selectBook(bookId,bookListIndex) {

        $scope.selectedBook = {
            'bookId': bookId,
            'bookListIndex': bookListIndex
        };

        // stores the values in the cookie
        $cookieStore.put('selectedBook', $scope.selectedBook);

        // selects the proper tab
        $scope.BooksAPI.results.books[bookListIndex].active = true;

        // performs an API request to get the fragments list
        $scope.FragmentsAPI = FragmentsAPI.list({bookId:bookId});
    }

    // Defaults
    $scope.FragmentsAPI = {};

    // tries to retrieve a previously stored cookie
    $scope.selectedBook = $cookieStore.get('selectedBook');

    // performs a query to the API to get the book list
    $scope.BooksAPI = BooksAPI.list({}, function(promise){

        // if the query is successfull and there is at least on item in the book list
        if (!promise.errors && promise.results.books[0]) {

            // if a cookie has been found
            if (typeof $scope.selectedBook !== 'undefined' && $scope.selectedBook.bookId) {
		
                // and if the value stored in the cookie matches an existent book
                angular.forEach(promise.results.books, function(book, index) {
                    if (book.id === $scope.selectedBook.bookId) {
                        selectBook(book.id, index);
                    }
                });

            } else {
		
                // I select the 1st book in the booklist (default)
                selectBook(promise.results.books[0].id, 0);

		// I get the first book returned
		$scope.BookAPI = BookAPI.get({bookId: promise.results.books[0].id});
            }
        }
    });

    if(typeof $scope.selectedBook !== 'undefined' && $scope.selectedBook.bookId){
        $scope.BookAPI = BookAPI.get({bookId: $scope.selectedBook.bookId});
    }

    // this function is activated when the user clicks on one of the vertical tabs
    $scope.selectThisBook = function (bookId,bookListIndex) {
        selectBook(bookId,bookListIndex);
    }

    $scope.sortableOptions = {

        stop: function(e, ui) {
            var indexes = [];
            angular.forEach($scope.FragmentsAPI.results.fragments, function(ref, index) {
                indexes.push(ref.id);
            });

            FragmentsAPI.sort({bookId: $scope.selectedBook.bookId, order:indexes}, function() {});
        }

    }

    $scope.synchronizeDb = function() {
         $scope.FragmentsAPI = FragmentsAPI.sync({bookId: $scope.selectedBook.bookId});
    }

    $scope.setChildStatus = function(fragment, child) {

        if (child) {
            FragmentAPI.setChild({fragmentId: fragment.id}, function(promise){
                if (!promise.error){
                    fragment.child = 1;
                }
            });
        } else {
            FragmentAPI.setParent({fragmentId: fragment.id}, function(promise){
                if (!promise.error){
                    fragment.child = 0;
                }
            });
        }
    }

    $scope.deleteFragment = function(fragmentId) {
        FragmentAPI.delete({fragmentId:fragmentId}, function(promise){
            if (!promise.errors) {
                angular.forEach($scope.FragmentsAPI.results.fragments, function(fragment, index) {
                    if (fragment.id === fragmentId) {
                        // removes the fragment item from the fragments list.
                        // this avoids a new fragmentsAPI.list() call
                        $scope.FragmentsAPI.results.fragments.splice(index,1);
                    }
                });
            }
        });
    }

    $scope.updateFragment = function(fragment){
        FragmentAPI.update({fragmentId: fragment.id, fragment: fragment}, function(promise) {
            if (!promise.error) {
                return 0; //closes the form
            }
            return 1; //leaves the form open
        });
    }

    $scope.setAsParentFragment = function(fragment) {
        FragmentAPI.setParent({fragmentId: fragment.id}, function(promise){
            if (!promise.error){
                fragment.child = 0;
            }
        });
    }

    $scope.renderBook = function() {
        Alerts.add('info', 'The book will be rendered. Please wait');
        BookAPI.render({bookId: $scope.selectedBook.bookId}, function(promise) {
            if (!promise.error) {
                Alerts.add('success', 'The book rendering is finished');
                $scope.BookAPI = BookAPI.get({bookId: $scope.selectedBook.bookId});
            } else {
                Alerts.add('danger', 'The book rendering failed');
            }
        });
    }
}]);
