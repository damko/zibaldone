<?php
namespace Zibaldone\Api;

use Zibaldone\Api\Tag as Tag;
use Zibaldone\Api\RelatedTag as RelatedTag;

use Illuminate\Database\Eloquent\Model as Eloquent;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local as Adapter;
use Parsedown as MarkdownParser;

class Article extends Eloquent {

    protected $database = 'zibaldone';
    protected $table = 'articles';
    protected $connection = 'mysql';
    public $timestamps = false;

    public static function syncDbWithFs()
    {
        // drops the relationships article - tags
        RelatedTag::truncate();

        // drops the articles
        self::truncate();

        // drops the tags
        Tag::truncate();

        // fake tag
        $tag = new Tag();
        $tag->name = 'no-tag';
        $tag->save();

        $allowed_exts = array('txt', 'md');

        $filesystem = new Filesystem(new Adapter(ARTICLES_REPO));

        foreach ($filesystem->listContents() as $item) {

            if ($item['type'] == 'file' && in_array($item['extension'], $allowed_exts)) {
                
                //reads the meta from file
                $meta = self::read_file_meta($filesystem->read($item['path']));
                
                //creates the note
                if (!isset($meta['title']) || empty($meta['title'])) {
                    continue;
                }
                 
                //adds the article   
                $article = new Article();
                $article->title = $meta['title'];
                $article->description = $meta['description'];
                $article->thumbnail = $meta['thumbnail'];
                $article->full_filename = $item['path'];
                if (! $article->save()) {
                    return false;
                }

                if (count($meta['tags']) == 0) {
                    $relatedTag = new relatedTag();
                    $relatedTag->tag_id = 1;
                    $relatedTag->article_id = $article->id;
                    $relatedTag->save();
                    continue;
                }

                // updates the tags list
                foreach ($meta['tags'] as $tag_name) {
                    $tag = new Tag();
                    //$clean_tag_name = strtolower(preg_replace('/[^A-Za-z0-9\-_ ]/', '', trim($tag_name)));
                    $tag->name = $tag_name;
                    if (! $tag->save()) {
                        return false;
                    }
                }

                // updates the relationships between the article and the tags
                foreach ($meta['tags'] as $tag_name) {
                    $clean_tag_name = strtolower(preg_replace('/[^A-Za-z0-9\-_ ]/', '', trim($tag_name)));
                    if ($tag = Tag::where('name', $clean_tag_name)->first()) {
                        $relatedTag = new relatedTag();
                        $relatedTag->tag_id = $tag->id;
                        $relatedTag->article_id = $article->id;
                        if (! $relatedTag->save()) {
                            return false;
                        }
                    }
                }

            }

        }

        return true;
    }

    public function tags()
    {
        return $this->belongsToMany('Zibaldone\Api\Tag', 'related_tags', 'article_id', 'tag_id');
    }

    public function getTags()
    {
        return $this->load('tags')->toArray()['tags'];
    }

    public static function list_all() 
    {
        $articles = array();
     
        // implements Eloquent Lazy Loading to retrieve the articles and the tags
        foreach (self::all()->sortBy(function($article){return $article->title;}) as $article) {
            $articles[] = $article->load('tags')->toArray();
        }
        
        return $articles;
    }

    public function getContent(){
        $filesystem = new Filesystem(new Adapter(ARTICLES_REPO));
        return $filesystem->read($this->full_filename);
    }

    public function toHtml($content){

        // Removes the comments and meta
        $content = preg_replace('#/\*.+?\*/#s', '', $content); 

        $converter = new MarkdownParser();
            //$html = $converter->convertToHtml($content);
            $html = $converter->text($content);
            $style = '
<style>
ul {
    list-style: disc;
    margin-left: 1.3em;
}
ol {
    list-style: decimal !important;
    padding-top: 0.5em;
    padding-bottom: 1em;
    margin-left: 2em;
}
ol > li {
    margin-bottom: 0.2em;
}
</style>
            ';

            // this is the standard github flawored markdown stylesheet
            $style = '
    <style>
    body
    {
       font-size:15px;
       line-height:1.7;
       overflow-x:hidden;

        background-color: white;

        font-family: Helvetica, arial, freesans, clean, sans-serif;
        /*width: 912px;
        padding: 30px;
        margin: 2em auto;
        */
        color:#333333;
    }

    .body-classic{
      color:#444;
      font-family:Georgia, Palatino, "Palatino Linotype", Times, "Times New Roman", "Hiragino Sans GB", "STXihei", "微软雅黑", serif;
      font-size:16px;
      line-height:1.5em;
      background:#fefefe;
      width: 45em;
    /*  margin: 10px auto;
      padding: 1em;
      outline: 1300px solid #FAFAFA;
    */
    }

    body>:first-child
    {
      margin-top:0!important;
    }

    body>:last-child
    {
      margin-bottom:0!important;
    }

    blockquote,dl,ol,p,pre,table,ul {
      border: 0;
      margin: 15px 0;
      padding: 0;
    }

    body a {
      color: #4183c4;
      text-decoration: none;
    }

    body a:hover {
      text-decoration: underline;
    }

    body a.absent
    {
      color:#c00;
    }

    body a.anchor
    {
      display:block;
      padding-left:30px;
      margin-left:-30px;
      cursor:pointer;
      position:absolute;
      top:0;
      left:0;
      bottom:0
    }

    .octicon{
      font:normal normal 16px sans-serif;
      width: 1em;
      height: 1em;
      line-height:1;
      display:inline-block;
      text-decoration:none;
      -webkit-font-smoothing:antialiased
    }

    .octicon-link:before{
      content:"\a0";
    }

    body h1,body h2,body h3,body h4,body h5,body h6{
      margin:1em 0 15px;
      padding:0;
      font-weight:bold;
      line-height:1.7;
      cursor:text;
      position:relative
    }

    body h1 .octicon-link,body h2 .octicon-link,body h3 .octicon-link,body h4 .octicon-link,body h5 .octicon-link,body h6 .octicon-link{
      display:none;
      color:#000
    }

    body h1:hover a.anchor,body h2:hover a.anchor,body h3:hover a.anchor,body h4:hover a.anchor,body h5:hover a.anchor,body h6:hover a.anchor{
      text-decoration:none;
      line-height:1;
      padding-left:0;
      margin-left:-22px;
      top:15%
    }

    body h1:hover a.anchor .octicon-link,body h2:hover a.anchor .octicon-link,body h3:hover a.anchor .octicon-link,body h4:hover a.anchor .octicon-link,body h5:hover a.anchor .octicon-link,body h6:hover a.anchor .octicon-link{
      display:inline-block
    }

    body h1 tt,body h1 code,body h2 tt,body h2 code,body h3 tt,body h3 code,body h4 tt,body h4 code,body h5 tt,body h5 code,body h6 tt,body h6 code{
      font-size:inherit
    }

    body h1{
      font-size:2.5em;
      border-bottom:1px solid #ddd
    }

    body h2{
      font-size:2em;
      border-bottom:1px solid #eee
    }

    body h3{
      font-size:1.5em
    }

    body h4{
      font-size:1.2em
    }

    body h5{
      font-size:1em
    }

    body h6{
      color:#777;
      font-size:1em
    }

    body p,body blockquote,body ul,body ol,body dl,body table,body pre{
      margin:15px 0
    }

    body h1 tt,body h1 code,body h2 tt,body h2 code,body h3 tt,body h3 code,body h4 tt,body h4 code,body h5 tt,body h5 code,body h6 tt,body h6 code
    {
      font-size:inherit;
    }

    body hr
    {
      background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAYAAAAECAYAAACtBE5DAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyJpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMC1jMDYwIDYxLjEzNDc3NywgMjAxMC8wMi8xMi0xNzozMjowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNSBNYWNpbnRvc2giIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6OENDRjNBN0E2NTZBMTFFMEI3QjRBODM4NzJDMjlGNDgiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6OENDRjNBN0I2NTZBMTFFMEI3QjRBODM4NzJDMjlGNDgiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDo4Q0NGM0E3ODY1NkExMUUwQjdCNEE4Mzg3MkMyOUY0OCIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDo4Q0NGM0E3OTY1NkExMUUwQjdCNEE4Mzg3MkMyOUY0OCIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PqqezsUAAAAfSURBVHjaYmRABcYwBiM2QSA4y4hNEKYDQxAEAAIMAHNGAzhkPOlYAAAAAElFTkSuQmCC);
      background-repeat: repeat-x;

      background-color: transparent;
      background-position: 0;
      border:0 none;
      color:#ccc;
      height:4px;
      margin:15px 0;
      padding:0;
    }

    body li p.first
    {
      display:inline-block;
    }

    body ul,body ol
    {
      padding-left:30px;
    }

    body ul.no-list,body ol.no-list
    {
      list-style-type:none;
      padding:0;
    }

    body ul ul,body ul ol,body ol ol,body ol ul
    {
      margin-bottom:0;
      margin-top:0;
    }

    body dl
    {
      padding:0;
    }

    body dl dt
    {
      font-size:14px;
      font-style:italic;
      font-weight:700;
      margin-top:15px;
      padding:0;
    }

    body dl dd
    {
      margin-bottom:15px;
      padding:0 15px;
    }

    body blockquote
    {
      border-left:4px solid #DDD;
      color:#777;
      padding:0 15px;
    }

    body blockquote>:first-child
    {
      margin-top:0;
    }

    body blockquote>:last-child
    {
      margin-bottom:0;
    }

    body table
    {
      display:block;
      overflow:auto;
      width:100%;
    }

    body table th
    {
      font-weight:700;
    }

    body table th,body table td
    {
      border:1px solid #ddd;
      padding:6px 13px;
    }

    body table tr
    {
      background-color:#fff;
      border-top:1px solid #ccc;
    }

    body img
    {
      -moz-box-sizing:border-box;
      box-sizing:border-box;
      max-width:100%;
    }

    body span.frame
    {
      display:block;
      overflow:hidden;
    }

    body span.frame>span
    {
      border:1px solid #ddd;
      display:block;
      float:left;
      margin:13px 0 0;
      overflow:hidden;
      padding:7px;
      width:auto;
    }

    body span.frame span img
    {
      display:block;
      float:left;
    }

    body span.frame span span
    {
      clear:both;
      color:#333;
      display:block;
      padding:5px 0 0;
    }

    body span.align-center
    {
      clear:both;
      display:block;
      overflow:hidden;
    }

    body span.align-center>span
    {
      display:block;
      margin:13px auto 0;
      overflow:hidden;
      text-align:center;
    }

    body span.align-center span img
    {
      margin:0 auto;
      text-align:center;
    }

    body span.align-right
    {
      clear:both;
      display:block;
      overflow:hidden;
    }

    body span.align-right>span
    {
      display:block;
      margin:13px 0 0;
      overflow:hidden;
      text-align:right;
    }

    body span.align-right span img
    {
      margin:0;
      text-align:right;
    }

    body span.float-left
    {
      display:block;
      float:left;
      margin-right:13px;
      overflow:hidden;
    }

    body span.float-left span
    {
      margin:13px 0 0;
    }

    body span.float-right
    {
      display:block;
      float:right;
      margin-left:13px;
      overflow:hidden;
    }

    body span.float-right>span
    {
      display:block;
      margin:13px auto 0;
      overflow:hidden;
      text-align:right;
    }

    body code,body tt
    {
      background-color:#f8f8f8;
      border:1px solid #ddd;
      border-radius:3px;
      margin:0 2px;
      padding:0 5px;
    }

    body code
    {
      white-space:nowrap;
    }

    code,pre{
      font-family:Consolas, "Liberation Mono", Courier, monospace;
      font-size:12px
    }

    body pre>code
    {
      background:transparent;
      border:none;
      margin:0;
      padding:0;
      white-space:pre;
    }

    body .highlight pre,body pre
    {
      background-color:#f8f8f8;
      border:1px solid #ddd;
      font-size:13px;
      line-height:19px;
      overflow:auto;
      padding:6px 10px;
      border-radius:3px
    }

    body pre code,body pre tt
    {
      background-color:transparent;
      border:none;
      margin:0;
      padding:0;
    }

    body .task-list{
      list-style-type:none;
      padding-left:10px
    }

    .task-list-item{
      padding-left:20px
    }

    .task-list-item label{
      font-weight:normal
    }

    .task-list-item.enabled label{
      cursor:pointer
    }

    .task-list-item+.task-list-item{
      margin-top:5px
    }

    .task-list-item-checkbox{
      float:left;
      margin-left:-20px;
      margin-top:7px
    }

    .highlight{
      background:#ffffff
    }

    .highlight .c{
      color:#999988;
      font-style:italic
    }

    .highlight .err{
      color:#a61717;
      background-color:#e3d2d2
    }

    .highlight .k{
      font-weight:bold
    }

    .highlight .o{
      font-weight:bold
    }

    .highlight .cm{
      color:#999988;
      font-style:italic
    }

    .highlight .cp{
      color:#999999;
      font-weight:bold
    }

    .highlight .c1{
      color:#999988;
      font-style:italic
    }

    .highlight .cs{
      color:#999999;
      font-weight:bold;
      font-style:italic
    }

    .highlight .gd{
      color:#000000;
      background-color:#ffdddd
    }

    .highlight .gd .x{
      color:#000000;
      background-color:#ffaaaa
    }

    .highlight .ge{
      font-style:italic
    }

    .highlight .gr{
      color:#aa0000
    }

    .highlight .gh{
      color:#999999
    }

    .highlight .gi{
      color:#000000;
      background-color:#ddffdd
    }

    .highlight .gi .x{
      color:#000000;
      background-color:#aaffaa
    }

    .highlight .go{
      color:#888888
    }

    .highlight .gp{
      color:#555555
    }

    .highlight .gs{
      font-weight:bold
    }

    .highlight .gu{
      color:#800080;
      font-weight:bold
    }

    .highlight .gt{
      color:#aa0000
    }

    .highlight .kc{
      font-weight:bold
    }

    .highlight .kd{
      font-weight:bold
    }

    .highlight .kn{
      font-weight:bold
    }

    .highlight .kp{
      font-weight:bold
    }

    .highlight .kr{
      font-weight:bold
    }

    .highlight .kt{
      color:#445588;
      font-weight:bold
    }

    .highlight .m{
      color:#009999
    }

    .highlight .s{
      color:#d14
    }

    .highlight .n{
      color:#333333
    }

    .highlight .na{
      color:#008080
    }

    .highlight .nb{
      color:#0086B3
    }

    .highlight .nc{
      color:#445588;
      font-weight:bold
    }

    .highlight .no{
      color:#008080
    }

    .highlight .ni{
      color:#800080
    }

    .highlight .ne{
      color:#990000;
      font-weight:bold
    }

    .highlight .nf{
      color:#990000;
      font-weight:bold
    }

    .highlight .nn{
      color:#555555
    }

    .highlight .nt{
      color:#000080
    }

    .highlight .nv{
      color:#008080
    }

    .highlight .ow{
      font-weight:bold
    }

    .highlight .w{
      color:#bbbbbb
    }

    .highlight .mf{
      color:#009999
    }

    .highlight .mh{
      color:#009999
    }

    .highlight .mi{
      color:#009999
    }

    .highlight .mo{
      color:#009999
    }

    .highlight .sb{
      color:#d14
    }

    .highlight .sc{
      color:#d14
    }

    .highlight .sd{
      color:#d14
    }

    .highlight .s2{
      color:#d14
    }

    .highlight .se{
      color:#d14
    }

    .highlight .sh{
      color:#d14
    }

    .highlight .si{
      color:#d14
    }

    .highlight .sx{
      color:#d14
    }

    .highlight .sr{
      color:#009926
    }

    .highlight .s1{
      color:#d14
    }

    .highlight .ss{
      color:#990073
    }

    .highlight .bp{
      color:#999999
    }

    .highlight .vc{
      color:#008080
    }

    .highlight .vg{
      color:#008080
    }

    .highlight .vi{
      color:#008080
    }

    .highlight .il{
      color:#009999
    }

    .highlight .gc{
      color:#999;
      background-color:#EAF2F5
    }

    .type-csharp .highlight .k{
      color:#0000FF
    }

    .type-csharp .highlight .kt{
      color:#0000FF
    }

    .type-csharp .highlight .nf{
      color:#000000;
      font-weight:normal
    }

    .type-csharp .highlight .nc{
      color:#2B91AF
    }

    .type-csharp .highlight .nn{
      color:#000000
    }

    .type-csharp .highlight .s{
      color:#A31515
    }

    .type-csharp .highlight .sc{
      color:#A31515
    }

    /* This is the customization for the sidebar and for the beginning of each fragment */
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
';
            return $style . $html;
    }

    public function save() {

        $this->title = ucfirst(preg_replace('/[^A-Za-z0-9\-_ ]/', '', trim($this->title)));

        return parent::save();
    }

    /**
     * Parses the file meta from the txt file header
     *
     * @param string $content the raw txt content
     * @return array $meta an array of meta values
     */
    protected function read_file_meta($content)
    {
        //global $config;

        $fields = array(
            'title'         => 'Title',
            'description'   => 'Description',
            'date'          => 'Date',
            'thumbnail'     => 'Thumbnail',
            'tags'          => 'Tags'
            //'author'      => 'Author',
            //'template'      => 'Template'
        );

        $meta = array();

        foreach ($fields as $field => $regex){

            switch ($field) {
                case 'tags':
                    if (preg_match('/^[ \t\/*#@]*' . preg_quote($regex, '/') . ':(.*)$/mi', $content, $match) && $match[1]){
                        $meta[ $field ] = trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $match[1]));
                        $meta[ $field ] = str_replace(', ', ',', $meta[ $field ]);
                        $meta[ $field ] = str_replace(' ,', ',', $meta[ $field ]);
                    } else {
                        $meta[ $field ] = '';
                    }
                break;
                
                default:
                    if (preg_match('/^[ \t\/*#@]*' . preg_quote($regex, '/') . ':(.*)$/mi', $content, $match) && $match[1]){
                        $meta[ $field ] = trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $match[1]));
                    } else {
                        $meta[ $field ] = '';
                    }
                break;
            }

        }

        //if(isset($headers['date'])) $headers['date_formatted'] = date($config['date_format'], strtotime($headers['date']));

        // only set $headers['tags'] if there are any
        if (strlen($meta['tags']) > 1) {
            $meta['tags'] = explode(',', $meta['tags']);
        } else {
            $meta['tags'] = NULL;
        }

        return $meta;
    }
}