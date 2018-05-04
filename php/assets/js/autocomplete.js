$(document).ready(function() {
    $('.snomed-concepts').autocomplete({
        source: "/api/json/search",
        minLength: 2,
        search: function(event, ui) {
            $(this).parent().addClass('loading');
        },
        open: function(event, ui) {
            $(this).parent().removeClass('loading');
        },
        create: function () {
            $(this).data('ui-autocomplete')._renderItem = function (ul, item) {
                return $("<li>")
                    .append('<div class="snomed-concept">' + item.description + '<div class="fsn">' + item.displayName + '|</div></div>')
                    .appendTo(ul);
            };
        }
    });

    $('.snomed-concepts.snomed-concepts-concepts').autocomplete({
        select: function(event, ui) {
            event.preventDefault();
            var concept = ui.item.value;
            var wrapper = $(this).parent().parent().find('.snomed-selection table tbody');
            var focuswrapper = $(this).parent().parent().find('.snomed-selection');

            $(focuswrapper).append('<div class="ui active inverted dimmer"><div class="ui active loader"></div></div>');

            $.getJSON('/api/json/concept/', {term: concept}, function (data) {
                var html = '<tr class="snomed-select">';
                html += '<td class="concept">';
                html += '<div class="selected-concept selected-row" data-concept="' + ui.item.id + '">';
                html += '<span>' + ui.item.displayName + '</span>';
                html += '<button class="circular ui icon button mini red inverted right floated remove-concept"><i class="icon remove"></i></button>';
                html += '</div></td>';
                html += '<td class="concept-checkbox self"><div class="ui toggle checkbox"><input type="checkbox" checked name="' + data.id + '" value="s" class="hidden" /></div></td>' + "\n";
                if (data.hasChildren) {
                    html += '<td class="concept-checkbox children"><div class="ui toggle checkbox"><input type="checkbox" checked name="' + data.id + '" value="c" class="hidden" /></div></td>' + "\n";
                }
                else {
                    html += '<td class="concept-checkbox children disabled"></td>' + "\n";
                }
                html += '</tr>' + "\n";

                $(wrapper).append(html);
                $('.ui.checkbox').checkbox();

                $(focuswrapper).find('.dimmer').remove();
            });

            $(this).val('');
            $(this).blur();
        }
    });

    $('.snomed-concepts.snomed-concepts-attributes').autocomplete({
        select: function(event, ui) {
            event.preventDefault();

            var concept = ui.item.value;
            var wrapper = $(this).parent().parent().find('.snomed-selection table tbody');
            var focuswrapper = $(this).parent().parent().find('.snomed-selection');

            $(focuswrapper).append('<div class="ui active inverted dimmer"><div class="ui active loader"></div></div>');

            $.getJSON('/api/json/concept/', {term: concept}, function (data) {
                var html = '<tr class="snomed-select">';
                html += '<td class="origin concept concept-attribute-concept">';
                html += '<div class="selected-concept selected-row" data-concept="' + ui.item.id + '">';
                html += '<span>' + data.displayName + '</span>';
                html += '<button class="circular ui icon button mini red inverted right floated remove-concept"><i class="icon remove"></i></button>';
                html += '</div></td>';

                if (data.hasChildren) {
                    html += '<td class="concept-checkbox children"><div class="ui toggle checkbox"><input type="checkbox" checked name="' + data.id + '" value="c" class="hidden" /></div></td>';
                }
                else {
                    html += '<td class="concept-checkbox children disabled"></td>';
                }

                html += '<td class="relation concept-attribute"><div class="ui mini form"><select class="dropdown" name="' + data.id + '">';

                for(i = 0; i < data.attributes.length; i++)
                {
                    var id = data.attributes[i].id;
                    var fsn = data.attributes[i].fsn;
                    var accepted = data.attributes[i].accepted;
                    html += '<option value="' + id + '" data-accepted="' + accepted + '">' + fsn + '</option>';
                }

                html += '</select></div>' + "\n" +
                    '</td>' + "\n" +
                    '<td class="attribute concept concept-attribute-concept">' + "\n" +
                    '<div class="search ui mini icon input full-width"><input class="snomed-concepts snomed-concepts-concept-attribute" data-parent="' + concept + '" type="text" placeholder="SNOMED CT concept"><i class="search icon"></i></div>' + "\n" +
                    '<div class="selected-attribute"></div></td>' + "\n" +
                    '</tr>' + "\n";

                $(wrapper).append(html);
                $('.ui.checkbox').checkbox();
                $('select.dropdown') .dropdown() ;

                var searchbox = $(wrapper).find('.snomed-concepts-concept-attribute');

                $(searchbox).autocomplete({
                    source: function(request, response) {
                        $.getJSON("/api/json/search", { term: request.term, parent: $(searchbox).parent().parent().parent().find('.relation select option:selected').attr('data-accepted') }, response);
                    },
                    minLength: 2,
                    search: function(event, ui) {
                        $(this).parent().addClass('loading');
                    },
                    open: function(event, ui) {
                        $(this).parent().removeClass('loading');
                    },
                    create: function () {
                        $(this).data('ui-autocomplete')._renderItem = function (ul, item) {
                            return $("<li>")
                                .append('<div class="snomed-concept">' + item.description + '<div class="fsn">' + item.displayName + '|</div></div>')
                                .appendTo(ul);
                        };
                    },

                    select: function(event, ui) {
                        event.preventDefault();

                        var searchwrapper = $(this).parent();
                        var attributewrapper = $(this).parent().parent().find('.selected-attribute');

                        var html = '<div class="selected-concept selected-attribute" data-concept="' + ui.item.id + '">';
                        html += '<span>' + ui.item.displayName + '</span>';
                        html += '<button class="circular ui icon button mini red inverted right floated remove-concept"><i class="icon remove"></i></button>';
                        html += '</div>';
                        $(attributewrapper).html(html).show();
                        $(searchwrapper).hide();

                        $(this).val('');
                        $(this).blur();
                    }
                });

                $(focuswrapper).find('.dimmer').remove();
            });

            $(this).val('');
            $(this).blur();
        }
    });

    $(document).on('click', 'button.remove-concept', function(e) {
        if($(this).parent().hasClass('selected-attribute'))
        {
            var parent = $(this).parent().parent();
            $(parent).html('').hide();
            $(parent).parent().find('.search').show().find('input').focus();
        }
        else if($(this).parent().hasClass('selected-row'))
        {
            $(this).parent().parent().parent().remove();
        }

        e.preventDefault();
    });
});