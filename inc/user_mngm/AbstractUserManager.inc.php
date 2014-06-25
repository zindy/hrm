<?php
// This file is part of the Huygens Remote Manager
// Copyright and license notice: see license.txt

require_once(dirname(__FILE__) . "/../System.inc.php");


/*!
  \class	AbstractUserManager
  \brief	Abstract base UserManager class that provides an interface for
            concrete classes to implement.

 */
abstract class AbstractUserManager {

    /*!
    \brief Return true if the UserManager can create users in the backing
           user management system (e.g. Active Directory or LDAP). If false,
           users will exist in the HRM as soon they are authenticated the
           the first time (e.g. by Active Directory). If true, they will
           be considered existing users only if they are stored in the HRM
           database.
    \return true if the UserManager can create users, false otherwise.
    */
    abstract public static function canCreateUsers();

    /*!
    \brief Return true if the UserManager can delete users.
    \see AbstractUserManager::canCreateUsers() for the concept.
    \return true if the UserManager can delete users, false otherwise.
     */
    abstract public static function canModifyUsers();

    /*!
    \param User $user User to be created (added to the HRM user database)
    */
    abstract public function createUser(User $user);

    /*!
    \param User $user User to be updated in the database.
    \
    \return true if the update was successful; false otherwise.
    */
    abstract public function updateUser(User $user);

    /*!
      \brief  Checks if user login is restricted to the administrator for
              maintenance (e.g. in case the database has to be updated).
      \return true if the user login is restricted to the administrator.
    */
    public function isLoginRestrictedToAdmin() {
        $result = !(System::isDBUpToDate());
        return $result;
    }

    /*!
    \brief  Checks whether the user has been suspended by the administrator.
    \param User $user User to be checked.
    \return true if the user was suspended by the administrator; false otherwise.
    */
    public function isSuspended(User $user)  {

        // The administrator is never suspended
        if ($user->isAdmin()) {
            return false;
        }

        // Get the authenticator and check the state
        $authenticator = AuthenticatorFactory::getAuthenticator(false);
        return $authenticator->isSuspended($user->name());
    }

    /*!
    \brief  Checks whether the user has been suspended by the administrator.
    \param User $user User to be checked.
    \return true if the user was suspended by the administrator; false otherwise.
    */
    public function isAccepted(User $user)  {

        // The administrator is always accepted
        if ($user->isAdmin()) {
            return true;
        }

        // Get the authenticator and check the state
        $authenticator = AuthenticatorFactory::getAuthenticator(false);
        return $authenticator->isAccepted($user->name());
    }

    /*!
    \brief  Checks whether a seed for a user creation request exists.

    \param String $seed Seed to be compared.

    This function returns false by default and must be reimplemented for those
    user management implementations that support this.

    \return true if a user with given seed exists, false otherwise
    */
    public function existsUserRequestWithSeed($seed = "ignored") {
        return false;
    }

    /*!
    \param User $user User for which to check for existence
    \
    \return true if the user exists; false otherwise.
    */
    public function existsInHRM(User $user) {
        $db = new DatabaseConnection();
        return ($db->checkUser($user->name()));
    }

};
