

$(function(){

    $('input[type=radio][name=table]').change(function() {
        loadAndShowIntervals();
    });

    loadAndShowIntervals();
    $("#btn-add").show();

    $(document).on("click", "kbd.int", function(e){
        var id = parseInt($(e.target).data("id"));
        var from = $(e.target).data("from");
        var to = $(e.target).data("to");
        var price = $(e.target).data("price");
        editActivate(id, from, to, price);
    } );


   $("#btn-clear").click(function () {
       $("#btn-clear").prop('disabled', true);
       $(".loader-clear").css('visibility', 'visible');

       $('#explain').html("");
       $('#explain-show').hide();

       $.getJSON("/", {
           action: "clear",
           table: tableVal(),
       }, function(data){
           if(data.status == "ok") {
               displayIntervals([]);
               $("#profile").fadeOut(800);
           }

           $("#btn-clear").prop('disabled', false);
           $(".loader-clear").css('visibility', 'hidden');
       });
   });

   $("#btn-explain").click(function(){
       $('#explain-show').html($('#explain').html());
       $('#explain-show').fadeIn(300);
   });

   $("#btn-delete").click(function () {

       $("#api-error").hide();
       $(".interval-control").prop('disabled', true);
       $(".loader-add").css('visibility', 'visible');

       $('#explain').html("");
       $('#explain-show').hide();

       $.getJSON("/", {
           action: "delete",
           table: tableVal(),
           profile: true,
           id: $("#int-id").val(),
       }, function (data) {
           if(data.status == "ok") {
               loadAndShowIntervals();
           } else {
               if(data.message) {
                   $("#api-error .error-text").html(data.message);
               } else {
                   $("#api-error .error-text").html(data);
               }

               $("#api-error").show();
           }

           $(".interval-control").prop('disabled', false);
           $(".loader-add").css('visibility', 'hidden');
       });
    });
    $("#btn-add").click(function () {
        addUpdate();
    });
    $("#btn-update").click(function () {
        addUpdate($("#int-id").val());
    });
    //$("#btn-update").click(addUpdate($("#int-id").val()));

   
});

function displayIntervals(intervals) {
    $("kbd").remove();

    intervals.forEach(function(int){
        var el ="    <kbd class='int' data-id='"+int[3]+"' data-from='"+int[0]+"' data-to='"+int[1]+"' data-price='"+int[2]+"'>\n" + int[0] + " " + int[1] +
            "        <span class=\"badge badge-pill badge-light\">"+int[2]+"</span>\n" +
            "    </kbd>";
        $(el).hide().appendTo("#intervals");
    });
    $("kbd").each(function(index) {
        $(this).delay(100*(index-1)).fadeIn(100);
    });

    $("#btn-update").hide();
    $("#btn-delete").hide();
}

function loadAndShowIntervals() {
    $.getJSON("/", {
        action: "list",
        table: tableVal(),
    }, function(data){
        if(data.status == "ok") {
            displayIntervals(data.message);
        } else {
            console.log(data);
        }
    });
}


function tableVal() {
    return $('input[name=table]:checked', '#table-selector').val();
}

function editActivate(id, from, to, price) {
    $("#date_start").val(from);
    $("#date_end").val(to);
    $("#price").val(price);

    $("#int-id").val(id);
    $("#btn-update").hide().fadeIn();
    $("#btn-delete").hide().fadeIn();
}

function addUpdate(intervalId) {
    $("#api-error").hide();
    $(".interval-control").prop('disabled', true);
    $(".loader-add").css('visibility', 'visible');

    $('#explain').html("");
    $('#explain-show').hide();

    var params = {
        action: "add",
        table: tableVal(),
        profile: true,
        id: $("#int-id").val(),
        date_start: $("#date_start").val(),
        date_end: $("#date_end").val(),
        price: $("#price").val(),
    };

    if(intervalId) {
        params.action = "update";
        params.id = intervalId;
    }

    $.getJSON("/", params, function (data) {
        if(data.status == "ok") {

            displayIntervals(data.message.intervals);
            profile(data.message.profile);

        } else {
            if(data.message) {
                $("#api-error .error-text").html(data.message);
            } else {
                $("#api-error .error-text").html(data);
            }

            $("#api-error").show();
        }

        $(".interval-control").prop('disabled', false);
        $(".loader-add").css('visibility', 'hidden');
    });
}

function profile(prf) {
    if(prf) {
        $("#profile").hide();
        console.log(prf);
        var html = "Data mutation queries: " + prf.queries.length + "\n\n";
        var i = 0;


        prf.queries.forEach(function(query){
            i++;
            html += "#" + i + " " + query.sql + "\nParams: " + query.params + " Rime: "+query.time+"ms\n\n";
        });

        html += "Total queries: " + prf.counters.total + " Time: " + prf.time + "ms";

        $("#profile").html(html);
        $("#profile").fadeIn(800);


        prf.all_queries.forEach(function(query){
            $('#explain').append( '<tr><td colspan="2"><code>' + query.sql + '</code></td></tr><tr>' );
            query.explain.values.forEach(function(row){
                row.forEach(function(val, i) {
                    $('#explain').append( '<tr><td class="k">'+query.explain.keys[i]+':</td><td class="v">'+val+'</td></tr>' );
                });

            });

            $('#explain').append( '<tr><td>&nbsp;</td></tr>');
        });
    }
}