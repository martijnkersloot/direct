<?php
require_once('classes/.classes.php');
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>DIRECT</title>
        <link rel="stylesheet"href="/assets/css/datatables.min.css"/>
        <link rel="stylesheet" href="/assets/css/jquery-ui.css">
        <link rel="stylesheet" href="/assets/css/oswald.css">
        <link rel="stylesheet" href="/assets/css/semantic.min.css" >
        <link rel="stylesheet" href="/assets/css/style.css" >
        <link rel="stylesheet" href="/assets/css/dataTables.semanticui.min.css" >
    </head>
    <body>
        <div id="top">
            <div id="header">
                <div id="logo">
                    <img src="/assets/img/direct_logo.png" />
                </div>
            </div>
            <div id="tabs">
                <div class="ui secondary inverted menu">
                    <a class="item active" data-item="in-text">
                        Input
                    </a>
                    <a class="item" data-item="in-concepts">
                        Concepts
                    </a>
                    <a class="item" data-item="in-attributes">
                        Attributes
                    </a>
                    <a class="item parse" data-item="out-results">
                        Processing
                    </a>
                </div>
            </div>
        </div>
        <div id="wrapper" class="ui grid">
            <div class="tab sixteen wide column in-tab active" id="in-text">
                <div class="ui grid">
                    <div class="eleven wide column">
                        <div class="ui form">
                            <div class="field">
                                <h3 class="ui dividing header">
                                    Text
                                </h3>
                                <textarea name="text" class="form-control text focus" id="text"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="five wide column">
                        <h3 class="ui dividing header">
                            File(s)
                        </h3>
                        <a href="#" class="ui fluid labeled icon button" id="btn-upload">
                            <i class="upload icon"></i>
                            Upload
                        </a>
                        <div id="upload-wrapper">
                            <form action="/api/json/upload" method="post" enctype="multipart/form-data" target="upload-frame">
                                <input type="file" name="files[]" multiple />
                            </form>
                            <iframe id="upload-frame" name="upload-frame" height="0" width="0" frameborder="0" scrolling="yes"></iframe>
                        </div>
                        <div id="uploaded-files" class="ui divided items"></div>
                    </div>
                </div>
                <div class="footer">
                    <a href="#" class="ui right labeled icon button btn-next">
                        <i class="right arrow icon"></i>
                        Concepts
                    </a>
                </div>
            </div>
            <div class="tab sixteen wide column in-tab ui form" id="in-concepts">
                <div class="ui grid">
                    <div class="eleven wide column">
                        <h3 class="ui dividing header">
                            Focus on concepts
                        </h3>
                        <div class="ui icon input snomed-input field full-width">
                            <input class="snomed-concepts snomed-concepts-concepts focus" type="text" placeholder="SNOMED CT concept">
                            <i class="search icon"></i>
                        </div>
                        <div id="focus-concepts" class="snomed-selection">
                            <table class="ui celled table">
                                <thead>
                                    <tr>
                                        <th>Concept</th>
                                        <th class="concept-checkbox">Concept</th>
                                        <th class="concept-checkbox">Children</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="five wide column">
                        <h3 class="ui dividing header">
                            Include concepts
                        </h3>
                        <div id="include-concepts" class="snomed-selection">
                            <table class="ui celled table">
                                <thead>
                                <tr>
                                    <th>Concept</th>
                                    <th class="concept-checkbox">Children</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="footer">
                    <a href="#" class="ui left labeled icon button btn-prev">
                        <i class="left arrow icon"></i>
                        Text
                    </a>
                    <a href="#" class="ui right labeled icon button btn-next">
                        <i class="right arrow icon"></i>
                        Attributes
                    </a>
                </div>
            </div>
            <div class="tab sixteen wide column in-tab ui form" id="in-attributes">
                <h3 class="ui dividing header">
                    Focus on attributes
                </h3>
                <div class="ui icon input snomed-input field full-width">
                    <input class="snomed-concepts snomed-concepts-attributes focus" type="text" placeholder="SNOMED CT concept">
                    <i class="search icon"></i>
                </div>
                <div id="focus-attributes" class="snomed-selection">
                    <table class="ui celled table">
                        <thead>
                        <tr>
                            <th class="concept-attribute-concept"></th>
                            <th class="concept-checkbox">Children</th>
                            <th class="concept-attribute">Attribute</th>
                            <th class="concept-attribute-concept"></th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="footer">
                    <a href="#" class="ui left labeled icon button btn-prev">
                        <i class="left arrow icon"></i>
                        Concepts
                    </a>
                    <a href="#" class="ui right labeled icon button btn-next">
                        <i class="right arrow icon"></i>
                        Parse
                    </a>
                </div>
            </div>
            <div class="tab sixteen wide column out-tab" id="out-results">
                <div class="ui grid">
                    <div class="three wide column no-space">
                        <div class="ui vertical fluid tabular menu" id="out-tabs">
                            <a class="item active" data-item="out-results-overview">
                                Overview
                            </a>
                        </div>
                    </div>

                    <div class="tab thirteen wide column active" id="out-results-overview">
                        <h3 class="ui dividing header">
                            Processing
                        </h3>

                        <div class="ui basic clearing no-space segment">
                            <button class="ui primary labeled icon button right floated btn-parse">
                                <i class="chevron circle right icon"></i>
                                Parse
                            </button>
                        </div>

                        <div class="output">
                        </div>
                    </div>

                </div>
                <div class="footer">
                    <a href="#" class="ui left labeled icon button btn-prev">
                        <i class="left arrow icon"></i>
                        Attributes
                    </a>
                </div>
            </div>
        </div>
        <div class="ui dimmer" id="dimmer-document">
            <div class="ui indeterminate text loader">Parsing document</div>
        </div>
        <script src="/assets/js/jquery.min.js"></script>
        <script src="/assets/js/jquery-ui.js"></script>
        <script src="/assets/js/semantic.min.js"></script>
        <script src="/assets/js/jquery.dataTables.min.js"></script>
        <script src="/assets/js/dataTables.semanticui.min.js"></script>
        <script src="/assets/js/modal.js"></script>
        <script src="/assets/js/upload.js"></script>
        <script src="/assets/js/parse.js"></script>
        <script src="/assets/js/script.js"></script>
        <script src="/assets/js/navigation.js"></script>
        <script src="/assets/js/autocomplete.js"></script>
    </body>
</html>
