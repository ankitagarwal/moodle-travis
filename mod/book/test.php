
<!doctype html>
<html>
<head>
    <style>
        body .modal {
            width: 96%; /* desired relative width */
            left: 2%; /* (100%-width)/2 */
            /* place center */
            margin-left:auto;
            margin-right:auto;

            height: 90%;
            bottom: 5%;
            top: 0;
            margin-bottom: auto;
            margin-top: auto;
        }
        .modal-dialog, .modal-content {
            height: 100%;
        }
        .modal .modal-body {
            max-height: 90%;
        }
        .leftnav {
            float: left;
        }
        .rightnav {
            float: right;
        }
    </style>
</head>
<body ng-app = "mod_book_display">

<div>
    <div class="row-fluid">
        <div ng-controller = "ctrl" ng-init = "init()">
            <p><button class="btn btn-primary" ng-click="openPopup()">Open Dialog</button></p>
        </div>
    </div>
</div>
<script src="angular/angular.js"></script>
<script src="angular/angular-sanitize.min.js"></script>
<script src="angular/ui-bootstrap-custom-0.10.0.min.js"></script>
<link href="//netdna.bootstrapcdn.com/bootstrap/2.3.2/css/bootstrap.min.css" rel="stylesheet">
<script src="angular/display.js"></script>
</body>
</html>