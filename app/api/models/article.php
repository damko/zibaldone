<?php
namespace Zibaldone\Api;

use Zibaldone\Api\Tag as Tag;
use Zibaldone\Api\RelatedTag as RelatedTag;

use Illuminate\Database\Eloquent\Model as Eloquent;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local as Adapter;
use League\CommonMark\CommonMarkConverter;

class Article extends Eloquent {

    const REPO = '../articles';
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

        $filesystem = new Filesystem(new Adapter(self::REPO));

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
    	// implements Eloquent Lazy Loading to retrieve the articles and the tags
    	//$articles = self::all()->take(2);
    	$articles = self::all();
    	return $articles->load('tags')->toArray();
    }

    public function getContent(){
    	$filesystem = new Filesystem(new Adapter(self::REPO));
    	return $filesystem->read($this->full_filename);
    }

    public function toHtml($content){

    	$content = preg_replace('#/\*.+?\*/#s', '', $content); // Remove comments and meta

    	$converter = new CommonMarkConverter();
        	$html = $converter->convertToHtml($content);
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
			'title'       	=> 'Title',
			'description' 	=> 'Description',
			'date' 			=> 'Date',
			'thumbnail'		=> 'Thumbnail',
			'tags'			=> 'Tags'
			//'author' 		=> 'Author',
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