var app = angular.module('app', []);

app.service('api5', function($rootScope, $http){
    //var apiUrl = 'http://localhost/proyectos/api5/services/api5.php';
    var apiUrl = '../services/api5.php';
    this.connect = (method, params) => {
        var data = new FormData();
        //params.username = 'seven';
        data.append('BIND', JSON.stringify(params));
        //data.append('method', method);
        data.append('SQL', ":app.logon");
        //data.append('sourcename', "default3");
        data.append('transactiontype', 'login');
        //data.append('logintype', 'DATABASE');
        data.append('logintype', 'OS');
        var req = {
            method: 'post',
            url: apiUrl,
            headers: {
                'X-Requested-With':'XMLHttpRequest'
                // SEND Basic Auth from header
                ,'Authorization':'Basic '+btoa(params.email + ":" + params.password)
            },
            data: data
        }
        return $http(req);
    }

})

console.log("APP Service", app.service);
app.controller('main', function($scope, api5){
    $scope.login = () =>{
        credentials = {
            email : $scope.email,
            password : $scope.password
        }
        api5.connect('login', credentials).then(response =>{
            console.log(`success: ${response.data}`);
        },error =>{
            //console.log(`error: ${error}`, $scope, error,`${response.data}`);
            if (error.data.ERROR.CODE == 'MYSQL-1329') {
                alert('Usuario / password invalidos');
            } else
            console.log('ERROR=',error.data.ERROR.CODE, '-', error.data.ERROR.MESSAGE);
        })
    }
    $scope.test = t =>{
        console.log(t)
    }
})