<?php

spl_autoload_register(function ($class) {

    // project-specific namespace prefix
    $prefix = 'Zibaldone\\Api\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = lcfirst(substr($class, $len));

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

use \Slim\Slim as Slim;
use Illuminate\Database\Capsule\Manager as Capsule;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local as Adapter;

use Zibaldone\Api\RelatedTag as RelatedTag;
use Zibaldone\Api\Tag as Tag;
use Zibaldone\Api\Book as Book;
use Zibaldone\Api\Fragment as Fragment;
use Zibaldone\Api\subreferenceTrait as SubreferenceTrait;
use Zibaldone\Api\GithubReference as GithubReference;
use Zibaldone\Api\Reference as Reference;
use Zibaldone\Api\Download as Download;
use Zibaldone\Api\Downloader as Downloader;
use Zibaldone\Api\Article as Article;

class Zibaldone extends Slim {

    public $httpStatus = 200;
    public $results = array();
    public $error_messages = array();
    public $notifications = array();

    public function __construct(array $slim_params)
    {
        $capsule = new Capsule();
        $settings = array(
                    'driver'    => 'mysql',
                    'host'      => 'localhost',
                    'database'  => 'zibaldone',
                    'username'  => 'zib',
                    'password'  => 'password',
                    'charset'   => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix'    => '',
                );
        $capsule->addConnection($settings, 'mysql');
        $capsule->bootEloquent();

        $this->error_messages[30] = 'Sorry, that book does not exist';
        $this->error_messages[31] = 'Sorry, that reference does not exist';
        $this->error_messages[32] = 'Sorry, that fragment does not exist';

        $this->error_messages[40] = 'Wrong parameters in input';
        $this->error_messages[41] = 'Wrong route';

        $this->error_messages[50] = 'I can not add the resource';
        $this->error_messages[51] = 'I can not delete the resource';
        $this->error_messages[52] = 'I can not update the resource';
        $this->error_messages[53] = 'I can not download the resource';
        $this->error_messages[54] = 'I can not synchronize the resource';

        $this->error_messages[60] = 'The resource already exists';

        return parent::__construct($slim_params);
    }

    private function prepareResponse(array $error_codes = null)
    {
        $response = array();

        // adds data to response
        $response['results'] = $this->results;

        // adds notifications to response. Useful only for 2XX http statuses
        if (count($this->notifications)) {
            $response['notifications'] = $this->notifications;
        }

        if (is_null($error_codes)) {
            return $response;
        }

        // add errors to response for clarification. Useful only for !2XX http statuses
        $errors = array();
        foreach ($error_codes as $error_code) {
            if ( in_array($error_code, array_keys($this->error_messages)) ) {
                array_push($errors, array('code' => $error_code, 'message' => $this->error_messages[$error_code]));
            }
        }

        if (count($errors)) {
            $response['errors'] = $errors;
        }

        return $response;
    }

    public function output(array $error_codes = null)
    {
        $response = $this->prepareResponse($error_codes);

        //TODO this should not be necessary as it is the standard, right?
        $this->response()->header("Content-Type", "application/json");

        if (is_array($response) || is_object($response)) {
            $response = json_encode($response);
        }

        $this->halt($this->httpStatus, $response);
    }

    public function listBooks()
    {
        //$this->results['books'] = Book::all()->toArray();

        $this->results['books'] = array();

        foreach (Book::all() as $book) {
            $book->render = $book->getRenderInfo();    
            $this->results['books'][] = $book->toArray();
        }
    }

    public function getBook($bookId)
    {
        if (! is_numeric($bookId)) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        if ( ! $book = Book::find($bookId)) {
            $this->httpStatus = 400;
            $this->output(array(30));
        }

        // adds info about rendering
        $book->render = $book->getRenderInfo();

        $this->results['book'] = $book->toArray();
    }

    public function addBook()
    {
        if (! $input = json_decode($this->request->getBody())) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        if (!isset($input->title) || empty(trim($input->title))) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        $input->title = trim($input->title);

        if ( strlen($input->title) < 3 || strlen($input->title) > 50) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        $book = new Book();

        $book->title = trim($input->title);

        if (!$book->save()) {
            $this->httpStatus = 500;
            $this->output(array(50));
        }

        $this->results['book'] = $book->toJson();
    }

    public function updateBook()
    {
        if (! $input = json_decode($this->request->getBody())) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        if (!isset($input->book->id) || !is_numeric($input->book->id)) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        if (!isset($input->book->title) || empty(trim($input->book->title))) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        if ( !$book = Book::find($input->book->id)) {
            $this->httpStatus = 404;
            $this->output(array(30));
        }

        $book->title = trim($input->book->title);

        if (!$book->update()) {
            $this->httpStatus = 400;
            $this->output(array(51));
        }

        array_push($this->notifications, 'Book updated with title: "' . $book->title . '"');
        $this->results['book'] = $book->toJson();
    }

    public function deleteBook()
    {
        $bookId = $this->request->get('id');

        if (is_null($bookId) || !is_numeric($bookId)) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        if ( !$book = Book::find($bookId)) {
            $this->httpStatus = 400;
            $this->output(array(30));
        }

        if (!$book->delete()) {
            $this->httpStatus = 400;
            $this->output(array(51));
        }
    }

    public function renderBook($bookId)
    {
        if (! is_numeric($bookId)) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        if ( ! $book = Book::find($bookId)) {
            $this->httpStatus = 400;
            $this->output(array(30));
        }

        $html_fragments = $book->fragmentsToHtml();

        $data = array();
        $data['title'] = $book->title;
        $data['fragments'] = $html_fragments;

        $view = $this->view();
        $view->setData($data);
        $html = $view->fetch('book_template.php');

        $book->store($html);
        $book->createIndex();
    }

    public function listReferences($bookId)
    {

        if (! is_numeric($bookId)) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        if ( ! $book = Book::find($bookId)) {
            $this->httpStatus = 400;
            $this->output(array(30));
        }

        $this->results['references'] = $book->listReferences();
    }

    public function addReference()
    {
        if (! $newReference = json_decode($this->request->getBody())) {
            // wrong input
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        if ( ! $book = Book::find($newReference->book_id)) {
            $this->httpStatus = 404;
            $this->output(array(30));
        }

        //TODO check if the user is adding a duplicated reference and return a proper message?

        $book->syncDbWithFs();

        $reference = new Reference();
        if (!$reference->add($newReference)) {
            $this->httpStatus = 500;
            $this->output(array(50));
        }

        $this->results['reference'] = Reference::find($reference->id)->toJson();
    }

    public function deleteReference($referenceId)
    {
        if ( !$reference = Reference::find($referenceId)) {
            $this->output();
        }

        if (!$reference->delete()) {
            $this->httpStatus = 400;
            $this->output(array(51));
        }
    }

    public function listFragments($bookId)
    {

        if (! is_numeric($bookId)) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        if ( ! $book = Book::find($bookId)) {
            $this->httpStatus = 400;
            $this->output(array(30));
        }

        $this->results['fragments'] = $book->orderedFragments->toArray();

    }

    public function syncFragments($bookId)
    {

        if (! is_numeric($bookId)) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        if ( ! $book = Book::find($bookId)) {
            $this->httpStatus = 400;
            $this->output(array(30));
        }

        // TODO shall this be a Book method?
        // writes down the downloads that are not in the filesystem
        // (this happens if the user deletes them from the folder or if the download failed at creation time)
        foreach ($book->references as $reference) {
            if ($download = $reference->subreference->download) {
                $download->store($book->getManuscriptPath(), 'donttouch');
            }
        }

        // synchronizes the fragments records with filesystem
        $book->syncDbWithFs();

        $this->results['fragments'] = $book->orderedFragments->toArray();
    }

    public function sortFragments($bookId)
    {
        $post = json_decode($this->request->getBody());

        if (! is_numeric($bookId) || !isset($post->order)) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        if ( ! Book::find($bookId)) {
            $this->httpStatus = 400;
            $this->output(array(30));
        }

        foreach ($post->order as $position => $fragment_id) {
            $fragment = Fragment::find($fragment_id);
            $fragment->position = $position + 1;
            $fragment->save();
        }
    }

    public function setChildStatus($fragmentId, $child)
    {

        if (! is_numeric($fragmentId)) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        if ( ! $fragment = Fragment::find($fragmentId)) {
            $this->httpStatus = 400;
            $this->output(array(30));
        }

        $fragment->child = $child;
        if (!$fragment->save()) {
            $this->httpStatus = 500;
            $this->output(array(52));
        }
    }

    public function deleteFragment($fragmentId)
    {
        if (! is_numeric($fragmentId)) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        if ( ! $fragment = Fragment::find($fragmentId)) {
            $this->httpStatus = 400;
            $this->output(array(30));
        }

        if (!$fragment->delete()) {
            $this->httpStatus = 400;
            $this->output(array(51));
        }
    }

    public function updateFragment($fragmentId)
    {
        if (! is_numeric($fragmentId)) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        if ( ! $fragment = Fragment::find($fragmentId)) {
            $this->httpStatus = 400;
            $this->output(array(32));
        }

        if (! $input = json_decode($this->request->getBody())) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        if (! isset($input->fragment->menu_label)) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        $fragment->menu_label = $input->fragment->menu_label;

        if (! $fragment->save()) {
            $this->httpStatus = 500;
            $this->output(array(51));
        }

        $this->results['fragment'] = $fragment->toArray();
    }


    public function listArticles()
    {
        $this->results['articles'] = Article::list_all();
    }

    public function getArticle($articleId)
    {
        if (! is_numeric($articleId)) {
            $this->httpStatus = 400;
            $this->output(array(40));
        }

        if ( ! $article = Article::find($articleId)) {
            $this->httpStatus = 400;
            $this->output(array(30));
        }

        $this->results['article'] = $article->toHtml($article->getContent());
    }


    public function syncArticles()
    {
        if (! Article::syncDbWithFs()) {
            $this->httpStatus = 500;
            $this->output(array(54));            
        }
    }

    public function listTags()
    {
        $this->results['tags'] = Tag::listTags();
    }
}
