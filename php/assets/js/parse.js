$(document).ready(function() {
    var concepts = [];
    var attributes = [];
    var results = [];
    var include = [];

    var freetext = null;

    $('#tabs a.item').click(function() {
        if ($(this).hasClass('parse')) {
            $('#out-tabs .item.out').remove();

            concepts = [];
            attributes = [];
            include = [];

            var header = '<th>File</th><th>Status</th>';
            var row = '';

            var i = 0;
            $('#focus-concepts tr.snomed-select').each(function () {
                var id = $(this).find('.concept .selected-concept').attr('data-concept');
                var fsn = $(this).find('.concept .selected-concept span').text();
                var self = $(this).find('.concept-checkbox.self input').is(':checked');
                var children = $(this).find('.concept-checkbox.children input').is(':checked');

                var concept = {conceptId: id, conceptSelf: self, conceptChildren: children};
                concepts.push(concept);

                header += '<th class="results-cell results-header tooltip" data-html="' + fsn + '" data-variation="inverted very wide small" data-position="top right">';
                header += id;
                header += '</th>';
                row += '<td class="results-cell results-cell-concept" data-id="' + i + '"></td>';
                i++;
            });

            var i = 0;
            $('#focus-attributes tr.snomed-select').each(function () {
                var id1 = $(this).find('.origin.concept-attribute-concept .selected-concept').attr('data-concept');
                var fsn1 = $(this).find('.origin.concept-attribute-concept .selected-concept span').text();
                var id2 = $(this).find('.attribute.concept-attribute-concept .selected-concept').attr('data-concept');
                var fsn2 = $(this).find('.attribute.concept-attribute-concept .selected-concept span').text();
                var children = $(this).find('.concept-checkbox.children input').is(':checked');
                var relation = $(this).find('.relation select').val();
                var fsnRelation = $(this).find('.relation select option:selected').text();

                var attribute = {
                    conceptId1: id1,
                    conceptId2: id2,
                    conceptChildren: children,
                    conceptRelation: relation
                };
                attributes.push(attribute);

                var tooltip = fsn1 + '<br />' + relation + '|' + fsnRelation + '|<br />' + fsn2;

                header += '<th class="results-cell results-header tooltip" data-html="' + tooltip + '" data-variation="inverted very wide small" data-position="top right"">';
                header += id1 + '<br/>';
                header += relation + '<br/>';
                header += id2;
                header += '</th>';

                row += '<td class="results-cell results-cell-attribute" data-id="' + i + '"></td>';
                i++;
            });

            $('#include-concepts tr.snomed-select').each(function () {
                if ($(this).find('.concept-checkbox input').is(':checked')) {
                    var id = $(this).find('.concept .selected-concept').attr('data-concept');

                    include.push(id);
                }
            });

            var html = '<table class="ui celled table">';
            var menu = '';
            html += '<thead>';
            if (concepts.length > 0 || attributes.length > 0) {
                html += '<tr><th colspan="2" class="results-header"></th>';
                if (concepts.length > 0) {
                    html += '<th class="results-header" colspan="' + concepts.length + '">Concepts</th>';
                }
                if (attributes.length > 0) {
                    html += '<th class="results-header" colspan="' + attributes.length + '">Attributes</th>';
                }
                html += '</tr>'
            }
            html += header + '</thead>';
            html += '<tbody>';

            if ($('#text').val().length > 0) {
                html += '<tr class="results-row" data-id="-1">';
                if ($('#text').val() == freetext && $('#out--1').length > 0) {
                    var currentRow = $('.results-row[data-id="-1"]').html();
                    html += currentRow;

                    menu += '<a class="item out" data-id="-1" data-item="out--1">';
                }
                else {
                    html += '<td>';
                    html += 'Text';
                    html += '</td>';
                    html += '<td class="status">Ready to parse</td>';
                    html += row;

                    menu += '<a class="item out disabled" data-id="-1" data-item="out--1">';

                    freetext = $('#text').val();
                }
                html += '</tr>';

                menu += 'Text';
                menu += '</a>';
            }

            $.each(files, function (i, value) {
                html += '<tr class="results-row" data-id="' + value.id + '" data-file="' + value.file + '">';

                if ($('#out-' + value.id + '[data-file="' + value.file + '"]').length > 0) {
                    var currentRow = $('.results-row[data-id=' + value.id + '][data-file="' + value.file + '"]').html();
                    html += currentRow;

                    menu += '<a class="item out" data-id="' + value.id + '" data-item="out-' + value.id + '">';
                }
                else {
                    html += '<td>';
                    html += value.file;
                    html += '</td>';
                    html += '<td class="status">Ready to parse</td>';
                    html += row;

                    menu += '<a class="item out disabled" data-id="' + value.id + '" data-file="' + value.file + '" data-item="out-' + value.id + '">';
                }

                html += '</tr>';

                menu += value.file;
                menu += '</a>';
            });

            html += '</tbody>';
            html += '</table>';

            $('#out-results .output').html(html);
            $('#out-tabs').append(menu);
            $('#out-results .output .tooltip').popup();
        }
    });

    $('.btn-parse').click(function () {
        checkRunning(function() {
            $(this).addClass('loading');
            $('#out-results-overview table .status').html('<i class="hourglass empty icon"></i> Queued');
            results = [];

            var input = $('#text').val();
            var length = files.length;

            if (input.length > 0) {
                parseFile(-1, length, concepts, attributes, include);
            }
            else if (length > 0) {
                parseFile(0, length, concepts, attributes, include);
            }

            $('#out-results .file-results').remove();
        });
    });
});

function parseFile(fileId, length, concepts, attributes, include)
{
    if(fileId == -1)
    {
        var id = fileId;
        var file = 'Text';
        var text = $('#text').val();
        var data = {text: text, concepts: concepts, attributes: attributes, include: include};
    }
    else
    {
        var id = files[fileId].id;
        var file = files[fileId].file;
        var data = {fileId: id, concepts: concepts, attributes: attributes, include: include};
    }

    var tableRow = $('tr.results-row[data-id=' + fileId + ']');
    var status = $(tableRow).find('.status');
    $(status).html('<div class="ui tiny inline loader active"></div> Parsing');

    $.ajax({
        type: 'POST',
        url: '/api/json/parse',
        data: data,
        success: function (data) {
            if (typeof data.error === 'undefined') {
                var rounded = Math.round(data.time.total * 100) / 100
                $(status).html('<i class="checkmark icon"></i> Finished in ' + rounded + 's');

                $('#out-tabs .item[data-id="' + id + '"]').removeClass('disabled');

                if (typeof data.focus.concepts !== 'undefined') {
                    $.each(data.focus.concepts, function (i, concept) {
                        var cell = $(tableRow).find('td.results-cell.results-cell-concept[data-id=' + i + ']');

                        var cellContent = '<i class="large icons">';

                        if (concept.found == true) {
                            $(cell).addClass('positive');
                            cellContent += '<i class="icon checkmark"></i>';

                            if (concept.polarity == true) {
                                cellContent += '<i class="corner icon checkmark"></i>';
                            }
                            else {
                                cellContent += '<i class="corner icon close"></i>';
                            }
                        }
                        else {
                            $(cell).addClass('negative');
                            cellContent += '<i class="icon close"></i>';
                        }
                        cellContent += '</i>';

                        $(cell).html(cellContent);
                    });
                }

                if (typeof data.focus.attributes !== 'undefined') {
                    $.each(data.focus.attributes, function (i, attribute) {
                        var cell = $(tableRow).find('td.results-cell.results-cell-attribute[data-id=' + i + ']');

                        if (attribute.found == true) {
                            $(cell).addClass('positive').html('<i class="icon large checkmark"></i>');
                        }
                        else {
                            $(cell).addClass('negative').html('<i class="icon large close"></i>');
                        }
                    });
                }

                var html = '';
                html += '<div class="tab thirteen wide column file-results" id="out-' + id + '" data-file="' + file + '">';
                html += '   <div class="ui grid file-results-grid">';
                html += '       <div id="text-' + id + '" class="ui five wide column file-results-text">' + data.formatted + '</div>';
                html += '       <div class="ui file-results-wrapper eleven wide column">';
                html += '           <div id="concepts-' + id + '" class="ui tab active"><table id="table-concepts-' + id + '" class="ui celled striped table" width="100%"></table></div>';
                html += '           <div id="relations-' + id + '" class="ui tab"><table id="table-relations-' + id + '" class="ui celled striped table" width="100%"></table></div>';
                html += '           <div id="attributes-' + id + '" class="ui tab"><table id="table-attributes-' + id + '" class="ui celled striped table" width="100%"></table></div>';
                html += '       <div class="ui bottom secondary inverted menu">';
                html += '           <a class="item active" data-item="concepts-' + id + '" data-file="' + id + '">Concepts</a>';
                html += '           <a class="item" data-item="relations-' + id + '" data-file="' + id + '">Relations</a>';
                html += '           <a class="item" data-item="attributes-' + id + '" data-file="' + id + '">Attributes</a>';
                html += '       </div>';
                html += '   </div>';
                html += '</div>';

                $('#out-results > .grid').append(html);

                var conceptColumns = [
                    {title: "ID"},
                    {title: "FSN"},
                    {title: "Begin"},
                    {title: "End"},
                    {title: "Text"},
                    {title: "Score"},
                    {title: "Polarity"}
                ];
                var conceptRows = [];

                $.each(data.concepts, function (i, concept) {
                    conceptRows.push([
                        concept.id,
                        concept.fsn,
                        concept.begin,
                        concept.end,
                        concept.text,
                        concept.score,
                        concept.polarity
                    ]);
                });

                var relationColumns = [
                    {title: "Orig."},
                    {title: "Orig. ID"},
                    {title: "Orig. FSN"},
                    {title: "Dest."},
                    {title: "Dest. ID"},
                    {title: "Dest. FSN"}
                ];
                var relationRows = [];

                $.each(data.relationships, function (i, relation) {
                    relationRows.push([
                        relation.origin.text,
                        relation.origin.id,
                        relation.origin.fsn,
                        relation.destination.text,
                        relation.destination.id,
                        relation.destination.fsn
                    ]);
                });

                var attributeColumns = [
                    {title: "Dest. ID"},
                    {title: "Dest. FSN"},
                    {title: "Attr. ID"},
                    {title: "Attr. FSN"},
                    {title: "Orig. ID"},
                    {title: "Orig. FSN"}
                ];
                var attributeRows = [];

                $.each(data.attributes, function (i, attribute) {
                    attributeRows.push([
                        attribute.destination.id,
                        attribute.destination.fsn,
                        attribute.attribute.id,
                        attribute.attribute.fsn,
                        attribute.origin.id,
                        attribute.origin.fsn
                    ]);
                });

                var height = $(window).height();
                var wrapper = $('#top').outerHeight();
                var footer = $('.footer').outerHeight();

                var height = height - wrapper - footer - 123;

                $('#table-concepts-' + id).dataTable({
                    data: conceptRows,
                    columns: conceptColumns,
                    scrollY: height,
                    "dom": '<"wrapper"t>',
                    paging: false,
                    columnDefs: [
                        {
                            targets: [2, 3],
                            visible: false,
                            searchable: false
                        }
                    ],
                    initComplete: function() {
                        $('#table-concepts-' + id + ' tr').hover(function () {
                            var position = $('#table-concepts-' + id).dataTable().fnGetPosition(this);

                            var begin = $('#table-concepts-' + id).dataTable().fnGetData(position)[2];
                            var end = $('#table-concepts-' + id).dataTable().fnGetData(position)[3];
                            var code = $('#table-concepts-' + id).dataTable().fnGetData(position)[0];

                            for (i = begin; i < end; i++) {
                                $('#text-' + id + ' div#' + i + ' .semantic-info[data-id="' + code + '"]').show().parent().addClass('active');
                            }

                            var maxHeight = Math.max.apply(null, $(".character.active").map(function () {
                                return 20 + $(this).find('.info-underline:visible').length * 6;
                            }).get());

                            if (maxHeight < 0) maxHeight = 20;

                            $.when($('#height').remove()).then(function () {
                                $('body').append('<style id="height">.character { height: ' + maxHeight + 'px; }');
                                var parent = $('#text-' + id + ' div#' + begin);

                                console.log('#text-' + id + ' div#' + begin);

                                $('#text-' + id).scrollTo(parent);
                            });
                        }, function () {
                            var position = $('#table-concepts-' + id).dataTable().fnGetPosition(this);

                            var begin = $('#table-concepts-' + id).dataTable().fnGetData(position)[2];
                            var end = $('#table-concepts-' + id).dataTable().fnGetData(position)[3];
                            var code = $('#table-concepts-' + id).dataTable().fnGetData(position)[0];

                            for (i = begin; i < end; i++) {
                                if (!$('#text-' + id + ' div#' + i + ' .semantic-info[data-id="' + code + '"]').parent().hasClass('filter')) {
                                    $('#text-' + id + ' div#' + i + ' .semantic-info[data-id="' + code + '"]').hide().removeClass('active');
                                }
                            }

                            var maxHeight = Math.max.apply(null, $(".character.active").map(function () {
                                return 20 + $(this).find('.info-underline:visible').length * 6;
                            }).get());

                            if (maxHeight < 0) maxHeight = 20;

                            $.when($('#height').remove()).then($('body').append('<style id="height">.character { height: ' + maxHeight + 'px; }'));
                        });
                    }
                });
                $('#table-relations-' + id).dataTable({
                    data: relationRows,
                    columns: relationColumns,
                    scrollY: height,
                    "dom": '<"wrapper"t>',
                    paging: false
                });
                $('#table-attributes-' + id).dataTable({
                    data: attributeRows,
                    columns: attributeColumns,
                    scrollY: height,
                    "dom": '<"wrapper"t>',
                    paging: false
                });

                var newFileId = fileId + 1;

                if (newFileId < length) {
                    parseFile(newFileId, length, concepts, attributes, include);
                }
                else {
                    $('.btn-parse').removeClass('loading');
                }
            }
            else {
                $('.btn-parse').removeClass('loading');
                $(status).html('<i class="remove icon"></i> Error');
                createModal('An error occurred', data.error, 'error');
            }
        },
        error: function (xhr, st, error) {
            var data = JSON.parse(xhr.responseText);

            $('.btn-parse').removeClass('loading');
            $(status).html('<i class="remove icon"></i> Error');
            var error = '';
            if (typeof data.error !== 'undefined') error = data.error;
            createModal('An error occurred', error, 'error');
        }
    });
}