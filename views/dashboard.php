<!DOCTYPE html>
<html lang="en">
<head>

    <link rel="stylesheet" href="/css/style.css" />
    <title>Interval management system</title>

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/css/bootstrap.min.css">

</head>
<body>

<div id="content" class="container">
    <h1>Intervals</h1>

    <small>Date format: 2018-10-25</small>
    <div class="form-inline form-add">
        <label for="date_start" class="mr-sm-2">Check in:</label>
        <input class="form-control interval-control mb-2 mr-sm-2" id="date_start">

        <label for="date_end" class="mr-sm-2">Check out:</label>
        <input class="form-control interval-control mb-2 mr-sm-2" id="date_end">

        <label for="price" class="mr-sm-2">Price:</label>
        <input class="form-control interval-control mb-2 mr-sm-2" id="price">

        <input type="hidden" id="int-id">
        <span class="controls">
            <button class="btn btn-success interval-control mb-2" id="btn-update">Update</button>
            <button class="btn btn-danger interval-control mb-2" id="btn-delete">Delete</button>
            <button class="btn btn-primary interval-control mb-2" id="btn-add">Add</button>
        </span>
        <img src="/img/loader.gif" class="loader-add" />


        &nbsp;&nbsp;&nbsp;&nbsp;
        <button class="btn btn-warning mb-1" id="btn-clear">Clear user data</button>
        <img src="/img/loader.gif" class="loader-clear" />

    </div>

    <div id="table-selector">
        Operations on table:<br>
        <small>Choose between clean table and full one, populated with 50M intervals for performance testing. </small>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="table" id="table1" value="main" checked>
            <label class="form-check-label" for="table1">
                Main clean table
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="table" id="table2" value="full">
            <label class="form-check-label" for="table2">
                Full table (50M records)
            </label>
        </div>
    </div>


    <div class="alert alert-danger mb-1" role="alert" id="api-error">
        <strong>Error</strong> <span class="error-text"></span>
    </div>

    <hr>
        <p>
            <small>
                <ul>
                    <li>Check out date should be greater than check in date and it's not included in the interval length</li>
                    <li>Click on the interval to perform delete/update operations</li>
                    <li>Algorithm will find optimal DB manipulations to inject new or update existing interval</li>
                </ul>

            </small>
        </p>
    <hr>

    <div id = "intervals"></div>

    <hr>

    <pre id="profile"></pre>

    <button id="btn-explain"
            class="btn btn-dark"
            data-toggle="collapse"
            data-target="#explain-show"
    >Explain all queries</button>

    <table id="explain-show" class="table-dark table-striped"></table>
    <table id="explain" class="table-dark table-striped"></table>


    <br><br>

</div>

<script src="/js/jquery-3.4.1.min.js"></script>
<script src="/js/popper.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
<script src="/js/cb.js"></script>

</body>
</html>