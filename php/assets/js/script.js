$(document).ready(function() {
    var included = ["404684003", "362981000"]; // Clinical finding (finding), Qualifier value (qualifier value)

    var content;
    var dtHeight = 0;

    jQuery.fn.scrollTo = function(elem) {
        var elem2 = document.getElementById($(elem).attr('id'));
        if(elem2) {
            var elementOffsetTop = elem2.offsetTop;
            var height = $(elem).height();

            var calc = elementOffsetTop - (4 * height);

            $(this).scrollTop(calc);
        }
        return this;
    };

   $(window).resize(function() {
       var height = $(window).height();
       var wrapper = $('#top').outerHeight();
       var footer = $('.footer').outerHeight();

       content = height - wrapper - footer;

       dtHeight = content - 123;
       $('div.dataTables_scrollBody').height( dtHeight );
       $('#text').height( dtHeight );
    });

    $(window).resize();
    getUploadedFiles();

    $('#include-concepts').append('<div class="ui active loader"></div>');

    $.getJSON('/api/json/toplevel', function (data) {
        $.each(data, function(i, value) {
            var html =
                '<tr class="snomed-select">' + "\n" +
                '<td class="concept"><div class="selected-concept selected-row" data-concept="' + value.id + '">' + value.fsn + '</div></td>' + "\n" +
                '<td class="concept-checkbox children"><div class="ui toggle checkbox"><input type="checkbox" ';
            if(included.indexOf(value.id) > -1)
            {
                html += 'checked ';
            }
            html += 'name="' + value.id + '" value="c" class="hidden" /></div></td>' + "\n" +
                '</tr>' + "\n";

            $('#include-concepts.snomed-selection table tbody').append(html);
        });
        $('#include-concepts .loader').remove();
        $('#include-concepts table').show();
        $('.ui.checkbox').checkbox();
    });

    checkRunning(null);
});

function checkRunning(callback)
{
    $.getJSON('/api/json/check/', function(data) {
       if(data.running == false)
       {
           createModal('Cannot reach the cTAKES API', 'Please make sure that the Java Tomcat webserver is running.', 'error');
           return false;
       }
       else
       {
           if(callback != null) callback();
       }
    });
}