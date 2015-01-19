<?php

require_once 'vendor/autoload.php';
require_once 'models/zibaldone.php';

$zibaldone = new Zibaldone(array('debug' => true));

// ROUTES

// BOOKS COLLECTION
$zibaldone->get('/books', function () use ($zibaldone) {
    $zibaldone->listBooks();
    $zibaldone->output();
});



// SINGLE BOOK
$zibaldone->get('/book/:bookId', function ($bookId) use ($zibaldone) {
    $zibaldone->getBook($bookId);
    $zibaldone->output();
});

$zibaldone->get('/book/:bookId/:action', function ($bookId, $action) use ($zibaldone) {
    switch ($action) {
        case 'render':
            $zibaldone->renderBook($bookId);
        break;

        default:
            $this->httpStatus = 400;
            $this->output(array(41));
        break;
    }
    $zibaldone->output();
});

$zibaldone->post('/book', function () use ($zibaldone) {
    $zibaldone->addBook();
    $zibaldone->output();
});

$zibaldone->put('/book', function () use ($zibaldone) {
    $zibaldone->updateBook();
    $zibaldone->output();
});

$zibaldone->delete('/book', function () use ($zibaldone) {
    $zibaldone->deleteBook();
    $zibaldone->output();
});



// REFERENCES COLLECTION
$zibaldone->get('/references/:bookId', function ($bookId) use ($zibaldone) {
    $zibaldone->listReferences($bookId);
    $zibaldone->output();
});



// SINGLE REFERENCE
$zibaldone->post('/reference', function () use ($zibaldone) {
    $zibaldone->addReference();
    $zibaldone->output();
});

$zibaldone->delete('/reference/:referenceId', function ($referenceId) use ($zibaldone) {
    $zibaldone->deleteReference($referenceId);
    $this->output();
});



// FRAGMENTS COLLECTION
$zibaldone->get('/fragments/:bookId', function ($bookId) use ($zibaldone) {
    $zibaldone->listFragments($bookId);
    $zibaldone->output();
});

$zibaldone->get('/fragments/:bookId/:action', function ($bookId, $action) use ($zibaldone) {
    switch ($action) {
        case 'sync':
            $zibaldone->syncFragments($bookId);
        break;

        default:
            $this->httpStatus = 400;
            $this->output(array(41));
        break;
    }

    $zibaldone->output();
});

$zibaldone->post('/fragments/:bookId/:action', function ($bookId, $action) use ($zibaldone) {
    switch ($action) {
        case 'sort':
            $zibaldone->sortFragments($bookId);
        break;

        default:
            $this->httpStatus = 404;
            $this->output(array(41));
        break;
    }

    $zibaldone->output();
});



// SINGLE FRAGMENT
$zibaldone->put('/fragment/:fragmentId/:action', function ($fragmentId, $action) use ($zibaldone) {
    switch ($action) {

        case 'update':
            $zibaldone->updateFragment($fragmentId);
        break;

        case 'setchild':
            $zibaldone->setChildStatus($fragmentId, 1);
        break;

        case 'setparent':
            $zibaldone->setChildStatus($fragmentId, 0);
        break;

        default:
            $this->httpStatus = 400;
            $this->output(array(41));
        break;
    }

    $zibaldone->output();
});

$zibaldone->delete('/fragment/:fragmentId', function ($fragmentId) use ($zibaldone) {
    $zibaldone->deleteFragment($fragmentId);
    $zibaldone->output();
});

$zibaldone->run();
