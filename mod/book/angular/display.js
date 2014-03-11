var app = angular.module('mod_book_display', ['ui.bootstrap', 'ui.bootstrap.modal', 'ngSanitize', 'ngAnimate']);

app.controller('ctrl', function($scope, $http, $modal, $sce) {
    var dummy = {
        title: '',
        content: '',
        visible: false
    };
    $scope.toc = {};
    $scope.chapters = [];
    $scope.total = 0;
    $scope.title = '';

    $scope.init = function(id) {
        var data = {
            id: id,
            sesskey: 2
        };
        $http({method: 'GET', url: '/pgmaster/mod/book/book_ajax.php', params: data}).
            success(function(data) {
                console.log(data.chapters);
                $scope.toc = data.toc;
                $scope.title = $sce.trustAsHtml(data.title);
                var chapters = data.chapters;
                Object.keys(chapters).forEach(function (key){
                    var entry = chapters[key];
                    entry.content = $sce.trustAsHtml(entry.content);
                    entry.visible = false;
                    $scope.chapters.push(entry);
                });
                $scope.total = $scope.chapters.length;
                if ($scope.total % 2 == 1) {
                    // Add a dummy chapter to display at the end.
                    $scope.chapters.push(dummy);
                    $scope.total++;
                }

                // Set first two chapters as visible.
                $scope.chapters[0].visible = true;
                $scope.chapters[1].visible = true;
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
                chapters: function () {
                    return $scope.chapters
                },
                title : function () {
                    return $scope.title
                }
            }
        });
    };

    $scope.ModalInstanceCtrl = function ($scope, $modalInstance, chapters, title) {
        $scope.chapters = chapters;
        $scope.title = title;

        $scope.cancel = function () {
            $modalInstance.dismiss('cancel');
        };

        $scope.toggleChapter = function(key) {
            if (key % 2 === 0) {
                // Prev button
                $scope.prev(key);
            } else {
                console.log(key);
                $scope.next(key);
            }
        };

        $scope.next = function (k) {
            // No need to validate if next exist, this function wouldn't be called if didn't.
            $scope.chapters[k].visible = false;
            k--;
            $scope.chapters[k].visible = false;
            k += 2;
            $scope.chapters[k].visible = true;
            k++;
            $scope.chapters[k].visible = true;
        };

        $scope.prev = function (k) {
            // No need to validate if next exist, this function wouldn't be called if didn't
            $scope.chapters[k].visible = false;
            k++;
            $scope.chapters[k].visible = false;
            k -= 2;
            $scope.chapters[k].visible = true;
            k--;
            $scope.chapters[k].visible = true;
        };

        $scope.disableButton = function (key) {
            var p1,p2;
            // key starts at 0;
            if (key % 2 === 0) {
                // prev button.
                p1 = key - 1;
                p2 = key - 2;
                return !($scope.chapters[p1] && $scope.chapters[p2])
            } else {
                // next button.
                p1 = key + 1;
                p2 = key + 2;
                console.log(key);
                return !($scope.chapters[p1] && $scope.chapters[p2])
            }
        }

    };

});
