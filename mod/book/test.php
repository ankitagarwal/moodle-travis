

<!doctype html>
<html lang="en">
<head>
    <style>
        .modal {
            display: block;
        }
    </style>
</head>
<body ng-app = "mod_book_display">

<div ng-controller = "ctrl" ng-init = "init()">
    <button class="btn" ng-click="openPopup()">Open me!</button>
</div>

<script src="angular/angular.min.js"></script>
<script src="angular/bootstrap.min.js"></script>
<script src="angular/display.js"></script>

</body>
</html>