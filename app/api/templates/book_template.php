<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">


    <title><?php echo $this->data['title']; ?></title>

    <meta name="description" content="something here">
    <meta name="author" content="zibaldone">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap-theme.min.css">

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>

    <script type="text/javascript">
        $(function () {
          $('[data-toggle="popover"]').popover({html:true})
        })
    </script>
    <style>
        ul.side-nav {
            list-style-type: none;
            margin: 5px;
            margin-top: 60px;
            margin-right: 20px;
            padding: 0;
        }

        ul.side-nav li {
            !width: 100%;
            margin: 0;
            padding: 0;
        }
        ul.side-nav li.parent {
            font-size: 13px;
            padding-top: 5px;
            padding-bottom: 5px;
        }
        ul.side-nav li.child {
            font-size: 12px;
            margin-left: 10px;
            padding: 0;
            padding-bottom: 3px;
        }
        div.border-left {
            border: 0;
            border-left: 1px solid #e8e8e8;
        }
        section.fragment {
            border: 0;
        }
        div.fragment-header {
            padding: 0;
            margin: 0;
            margin-top: 40px;
            border: 0;
            border-top: 1px dashed #e8e8e8;
        }
        div.fragment-header > div.anchor {
            margin-top: 10px;
        }
        div.fragment-header > div.top {
            float: right;
            clear: left;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <a name="top"></a>
            <div class="col-xs-2 col-sm-2 col-md-2 col-lg-2">

                <ul class="side-nav">
<?php
foreach ($this->data['fragments'] as $key => $fragment) {
    if ($fragment['child'] == 0) {
        echo '<li class="parent"><a href="#fragment-' . $fragment['id'] . '">' . $fragment['menu_label'] . '</a></li>';
    } else {
        echo '<li class="child"><a href="#fragment-' . $fragment['id'] . '">' . $fragment['menu_label'] . '</a></li>';
    }
};
?>
                </ul>

            </div>

            <div class="col-xs-10 col-sm-10 col-md-10 col-lg-10 border-left">
<?php

// Book title
echo '<h1>' . $this->data['title'] . '</h1>';

foreach ($this->data['fragments'] as $key => $fragment) {

    echo '<section class="fragment">';
    // echo '<pre>' . print_r($fragment) . '</pre></section>';
    if ($key > 0) {
        echo '<div class="fragment-header">';
            echo '<div class="top"><a class="btn btn-default" href="#top">Top</a></div>';
            echo '<div class="anchor">';
                echo '<a name="fragment-' . $fragment['id']. '"></a>';
                if (isset($fragment['origin'])) {
                    $txt = 'Original fragment <a href=\'' . $fragment['origin'] . '\' target=\'_blank\'>here</a>';
                    echo '<button type="button" class="btn btn-info" data-toggle="popover" title="Additional info" data-content="'. $txt .'">Info</button>';
                    echo '<span style="margin-left: 10px; font-size: 9px;">' . $fragment['origin'] . '</span>';
                }
            echo '</div>';
        echo '</div>';
    }

    echo '<div>' . $fragment['content'] . '</div></section>';
};

?>
            </div>
        </div> <!-- row -->
    </div> <!-- container-fluid -->
</body>
</html>
