<?php

namespace Pecee\User;

interface IUserAuthentication
{

    public static function isLoggedIn();

    public static function createTicket($userId);

    public static function getTicket();

    public function signIn();

    public function signOut();

    public static function current();

    public static function authenticate($username, $password);

}