<?php

namespace Pecee\User;

interface IUserAuthentication
{

    public static function isLoggedIn(): bool;

    public static function createTicket(string $userId): void;

    public static function getTicket(): ?array;

    public function signIn(): void;

    public function signOut(): void;

    /**
     * @return static|null
     */
    public static function current(): ?self;

    /**
     * @param string $username
     * @param string $password
     * @return static
     */
    public static function authenticate(string $username, string $password): self;

}