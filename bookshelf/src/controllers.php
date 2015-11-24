<?php
/*
 * Copyright 2015 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Cloud\Samples\Bookshelf;

/*
 * Adds all the controllers to $app.  Follows Silex Skeleton pattern.
 */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app->get('/', function (Request $request) use ($app) {
    return $app->redirect('/books/');
});

$app->get('/books/', function (Request $request) use ($app) {
    /** @var DataModelInterface $model */
    $model = $app['bookshelf.model'];
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];
    $token = $request->query->get('page_token');
    $bookList = $model->listBooks($app['bookshelf.page_size'], $token);

    return $twig->render('list.html.twig', array(
        'books' => $bookList['books'],
        'next_page_token' => $bookList['cursor'],
    ));
});

$app->get('/books/add', function () use ($app) {
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];

    return $twig->render('form.html.twig', array(
        'action' => 'Add',
        'book' => array(),
    ));
});

$app->post('/books/add', function (Request $request) use ($app) {
    /** @var DataModelInterface $model */
    $model = $app['bookshelf.model'];
    /** @var ImageStorage $storage */
    $storage = $app['bookshelf.storage'];
    $files = $request->files;
    $book = $request->request->all();
    $image = $files->get('image');
    if ($image && $image->isValid()) {
        $book['imageUrl'] = $storage->storeFile(
            $image->getRealPath(),
            $image->getMimeType()
        );
    }
    $id = $model->create($book);

    return $app->redirect("/books/$id");
});

$app->get('/books/{id}', function ($id) use ($app) {
    /** @var DataModelInterface $model */
    $model = $app['bookshelf.model'];
    $book = $model->read($id);
    if (!$book) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];

    return $twig->render('view.html.twig', array('book' => $book));
});

$app->get('/books/{id}/edit', function ($id) use ($app) {
    /** @var DataModelInterface $model */
    $model = $app['bookshelf.model'];
    $book = $model->read($id);
    if (!$book) {
        return new Response('', Response::HTTP_NOT_FOUND);
    }
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];

    return $twig->render('form.html.twig', array(
        'action' => 'Edit',
        'book' => $book,
    ));
});

$app->post('/books/{id}/edit', function (Request $request, $id) use ($app) {
    $book = $request->request->all();
    $book['id'] = $id;
    /** @var ImageStorage $storage */
    $storage = $app['bookshelf.storage'];
    /** @var DataModelInterface $model */
    $model = $app['bookshelf.model'];
    $files = $request->files;
    $image = $files->get('image');
    if ($image && $image->isValid()) {
        $book['imageUrl'] = $storage->storeFile(
            $image->getRealPath(),
            $image->getMimeType()
        );
    }
    if ($model->update($book)) {
        return $app->redirect("/books/$id");
    }

    return new Response('', Response::HTTP_NOT_FOUND);
});

$app->post('/books/{id}/delete',
    function ($id) use ($app) {
        /** @var DataModelInterface $model */
        $model = $app['bookshelf.model'];
        $book = $model->read($id);
        if ($book) {
            $model->delete($id);

            return $app->redirect('/books/', Response::HTTP_SEE_OTHER);
        }

        return new Response('', Response::HTTP_NOT_FOUND);
    });