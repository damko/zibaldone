'use strict';

zibaldoneApp.factory('customInterceptor', [ 'Alerts', function(Alerts){
    return {
        response: function(response){

            if (typeof response.data !== 'object') {
                return response;
            }

            // console.log('response');
            // console.log(response);

            if (response.data.notifications) {

                angular.forEach(response.data.notifications, function(notification, index){
                    Alerts.add('success', notification);
                });

            }

            return response;
        },
        responseError: function (response) {

            if (typeof response.data !== 'object') {
                return response;
            }

            // console.log('responseError');
            // console.log(response);
            Alerts.add('danger', 'HTTP Status: ' + response.status + ' - HTTP Message: ' + response.statusText);

            if (response.data.errors) {
                angular.forEach(response.data.errors, function(error, index){
                    Alerts.add('danger', 'Error code: ' + error.code + ' - Error message: ' + error.message);
                });
            }

            return response;
        }
    }
}]);

zibaldoneApp.factory('BooksAPI', ['$resource', 'API_URL', function($resource, API_URL){
    return $resource(API_URL + '/books', {}, {
        list: { method: 'GET'},
    });
}]);

zibaldoneApp.factory('BookAPI', ['$resource', 'API_URL', function($resource, API_URL){
    return $resource(API_URL + '/book/:bookId/:action', {bookId: '@bookId', action: '@action'}, {
        get: { method: 'GET'},
        add: { method: 'POST'},
        delete: { method:'DELETE'},
        update: { method: 'PUT'},
        render: { method: 'GET', params: {action: 'render'}}
    });
}]);

zibaldoneApp.factory('ReferencesAPI', ['$resource', 'API_URL', function($resource, API_URL){
    return $resource(API_URL + '/references/:bookId', {bookId: '@bookId'}, {
        list: { method: 'GET'},
        //delete: {method:'DELETE'},
    });
}]);

zibaldoneApp.factory('ReferenceAPI', ['$resource', 'API_URL', function($resource, API_URL){
    return $resource(API_URL + '/reference/:referenceId', {referenceId: '@referenceId'}, {
        get: { method: 'GET'},
        add: { method: 'POST'},
        delete: {method:'DELETE'},
    });
}]);

zibaldoneApp.factory('FragmentsAPI', ['$resource', 'API_URL', function($resource, API_URL){

    return $resource(API_URL + '/fragments/:bookId/:action', {bookId: '@bookId', action: '@action'}, {
        list: { method: 'GET'},
        sync: { method: 'GET', params: {action: 'sync'}},
        sort:{ method: 'POST', params: {action: 'sort'}}
    });
}]);

zibaldoneApp.factory('FragmentAPI', ['$resource', 'API_URL', function($resource, API_URL){

    return $resource(API_URL + '/fragment/:fragmentId/:action', {fragmentId: '@fragmentId', action: '@action'}, {
        delete: {method:'DELETE'},
        update: { method: 'PUT', params: {action: 'update'}},
        setChild: {method: 'PUT', params: {action: 'setchild'}},
        setParent: {method: 'PUT', params: {action: 'setparent'}}
    });
}]);

zibaldoneApp.service('Alerts', [ function($rootScope, $timeout) {

    var list = [];

    //adds a new alert to the alerts queue
    this.add = function(type, msg) {

        var data = {
            type: type,
            created: Date.now(),
            hidden: false,
            msg: msg
        };

        list.push(data);

    };

    //returns the alerts list
    this.list = function() {
        return list;
    }

    //hides one alert
    this.hide = function(index) {
        if(list[index]){
            list[index].hidden = true;
        }
    }

    //deletes one alert
    this.delete = function(index) {
        if(list[index]){
            list.splice(index, 1);
        }
    }

    //deletes all the alerts
    this.deleteAll = function() {
        list.length = 0;
    };

    //deletes the hidden alerts
    this.deleteHidden = function() {
        angular.forEach(list, function(alertObj, key){
            if(alertObj.hidden){
                list.splice(key,1);
            }
        });
    };

}]);
