var app = angular.module('mod_book_display', ['ui.bootstrap', 'ui.bootstrap.modal']);

app.controller('ctrl', function($scope, $http, $modal) {
    $scope.toc = [];
    $scope.chapters = [];
    $scope.total = 0;
    $scope.current1 = '';
    $scope.current2 = '';

    $scope.init = function() {
        var data = {
            id: 2,
            sesskey: 2
        };
        $http({method: 'GET', url: '/pgmaster/mod/book/book_ajax.php', params: data}).
            success(function(data) {
                console.log(data);
                //return;
                $scope.toc = data.toc;
                $scope.chapters = data.chapters;
                $scope.total = $scope.chapters.length;
                if (data.chapters[1]) {
                    $scope.current1 = data.chapters[1];
                }
                if (data.chapters[2]) {
                    $scope.current2 = data.chapters[2];
                }
                console.log($scope);
            }).
            error(function() {
                alert('Cannot fetch book content, something went wrong');
            }
        );
    };

    $scope.openPopup = function () {
        var modalInstance = $modal.open({
            templateUrl: 'template/book.html',
            controller: $scope.ModalInstanceCtrl,
            resolve: {
                items: function () {
                    return $scope.items;
                }
            }
        });
    };

    $scope.ModalInstanceCtrl = function ($scope, $modalInstance, items) {
        $scope.items = items;
        $scope.cancel = function () {
            $modalInstance.dismiss('cancel');
        };

    };

    }
)