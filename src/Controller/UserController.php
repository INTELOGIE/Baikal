<?php

namespace Baikal\Controller;

use Baikal\Domain\User;
use Baikal\Domain\User\Username;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

class UserController implements ControllerProviderInterface {

    function connect(Application $app) {

        $controllers = $app['controllers_factory'];
        $controllers->get('/', [$this, 'indexAction'])->bind('admin_user_index');

        $controllers->get('/new',           [$this, 'createAction'])->bind('admin_user_create');
        $controllers->post('/new',          [$this, 'postCreateAction'])->bind('admin_user_create_post');
        $controllers->get('{user}',         [$this, 'editAction'])->bind('admin_user_edit');
        $controllers->post('{user}',        [$this, 'postEditAction'])->bind('admin_user_edit_post');
        $controllers->get('{user}/delete',  [$this, 'deleteAction'])->bind('admin_user_delete');
        $controllers->post('{user}/delete', [$this, 'postDeleteAction'])->bind('admin_user_delete_post');

        $controllers->get('{user}/calendars',                      CalendarController::class . '::indexAction')->bind('admin_user_calendars');
        $controllers->get('{user}/calendars/new',                  CalendarController::class . '::createAction')->bind('admin_calendar_create');
        $controllers->post('{user}/calendars/new',                 CalendarController::class . '::postCreateAction')->bind('admin_calendar_create_post');
        $controllers->get('{user}/calendars/{calendarId}',         CalendarController::class . '::editAction')->bind('admin_calendar_edit');
        $controllers->post('{user}/calendars/{calendarId}',        CalendarController::class . '::postEditAction')->bind('admin_calendar_edit_post');
        $controllers->get('{user}/calendars/{calendarId}/delete',  CalendarController::class . '::deleteAction')->bind('admin_calendar_delete');
        $controllers->post('{user}/calendars/{calendarId}/delete', CalendarController::class . '::postDeleteAction')->bind('admin_calendar_delete_post');

        $controllers->get('{user}/addressbooks',                         AddressBookController::class . '::indexAction')->bind('admin_user_addressbooks');
        $controllers->get('{user}/addressbooks/new',                     AddressBookController::class . '::createAction')->bind('admin_addressbook_create');
        $controllers->post('{user}/addressbooks/new',                    AddressBookController::class . '::postCreateAction')->bind('admin_addressbook_create_post');
        $controllers->get('{user}/addressbooks/{addressbookId}',         AddressBookController::class . '::editAction')->bind('admin_addressbook_edit');
        $controllers->post('{user}/addressbooks/{addressbookId}',        AddressBookController::class . '::postEditAction')->bind('admin_addressbook_edit_post');
        $controllers->get('{user}/addressbooks/{addressbookId}/delete',  AddressBookController::class . '::deleteAction')->bind('admin_addressbook_delete');
        $controllers->post('{user}/addressbooks/{addressbookId}/delete', AddressBookController::class . '::postDeleteAction')->bind('admin_addressbook_delete_post');


        $controllers->convert('user', function($user) use ($app) {
            if ($user === null) return;
            return $app['service.user']->getByUsername($user);
        });

        return $controllers;
    }

    function indexAction(Application $app) {
        $users = $app['service.user']->all();

        foreach ($users as $user) {
            $principalsUri = $user->getPrincipalUri();
            $user->calendarCount = count($app['sabredav.backend.caldav']->getCalendarsForUser($principalsUri));
            $user->addressbookCount = count($app['sabredav.backend.carddav']->getAddressBooksForUser($principalsUri));
        }

        return $app['twig']->render('admin/user/index.html', [
            'users'    => $users,
            'messages' => '',
            'form'     => '',
        ]);
    }

    function createAction(Application $app, Request $request) {

        if ($request->getMethod() !== Request::METHOD_GET) {
            throw new MethodNotAllowedException([Request::METHOD_GET]);
        }

        return $app['twig']->render('admin/user/create.html', [
            'user' => [
                'username'    => '',
                'displayName' => '',
                'email'       => '',
                'password'    => '',
            ],
        ]);
    }

    function postCreateAction(Application $app, Request $request) {

        if ($request->getMethod() !== Request::METHOD_POST) {
            throw new MethodNotAllowedException([Request::METHOD_POST]);
        }

        $userData = $request->get('data');
        if ($userData['password'] != $userData['passwordconfirm']) {
            throw new \InvalidArgumentException('Passwords did not match');
        }

        $user = User::fromPostForm($userData);
        $app['service.user']->create($user);
        $app['service.calendar']->provision($user);
        $app['service.addressbook']->provision($user);

        return $app->redirect($app['url_generator']->generate('admin_user_index'));
    }

    function editAction(Application $app, User $user) {

        if ($user === null) {
            $user = [
                'username'    => '',
                'displayName' => '',
                'email'       => '',
                'password'    => '',
            ];
        }

        return $app['twig']->render('admin/user/edit.html', [
            'user' => $user,
        ]);
    }

    function postEditAction(Application $app, Request $request, User $user) {

        $userData = $request->get('data');
        $userData['userName'] = $user->userName;
        if ($userData['password'] != $userData['passwordconfirm']) {
            throw new \InvalidArgumentException('Passwords did not match');
        }

        $user = User::fromPostForm($userData);
        $app['service.user']->update($user);

        return $app->redirect($app['url_generator']->generate('admin_user_index'));
    }

    function deleteAction(Application $app, User $user) {

        return $app['twig']->render('admin/user/delete.html', [
            'user' => $user,
        ]);
    }

    function postDeleteAction(Application $app, User $user) {

        $app['service.user']->remove($user);
        return $app->redirect($app['url_generator']->generate('admin_user_index'));
    }
}