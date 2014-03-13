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
                    entry.ngenter = false;
                    entry.ngenteractive = false;
                    entry.ngleave = false;
                    entry.ngleaveactive = false;
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
            var l = k + 1;
            $scope.chapters[l].visible = true;
            $scope.chapters[k].ngenter = true;
            $scope.chapters[l].ngenter = true;
            setTimeout($scope.updateClass(k, l , 'ngenteractive', $scope), 2000);

        };

        $scope.updateClass = function (k, l , prop, spe) {
            spe.chapters[k][prop] = true;
            spe.chapters[l][prop] = true;
            console.log(spe);
        }

        $scope.prev = function (k) {
            // No need to validate if next exist, this function wouldn't be called if didn't
            $scope.chapters[k].visible = false;
            k++;
            $scope.chapters[k].visible = false;
            k -= 2;
            $scope.chapters[k].visible = true;
            var l = k - 1;
            $scope.chapters[k].visible = true;
            $scope.chapters[k].ngleave = true;
            $scope.chapters[l].ngleave = true;
            $scope.chapters[k].ngenter = false;
            $scope.chapters[l].ngenter = false;
            $scope.chapters[k].ngenterleave = false;
            $scope.chapters[l].ngenterleave = false;

            setTimeout(function() {
                console.log(k);
                $scope.chapters[k].ngleaveactive = true;
                $scope.chapters[l].ngleaveactive = true;
            }, 1000);
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
                return !($scope.chapters[p1] && $scope.chapters[p2])
            }
        };

    };

});
